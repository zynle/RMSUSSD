# Choma Council USSD Revenue Platform — Technical Report

Status: **prototype, fully runtime-verified**. Built for Choma Council but designed so
any Zambian council can be onboarded by changing seed data and config only.

## 1. What this is

A Laravel 11 application implementing a full local-government revenue-collection USSD
journey (`sparors/laravel-ussd` state machine) covering:

- **Registration (KYC)** — mandatory for first-time dialers, matches the pptx flow
  (Last Name → Gender → First Name → Province → District → Constituency → Ward →
  optional Market/Shop info → Location → 4-digit PIN → confirm).
- **Levies**
  - Business Levy (New application / Renewal, computed from Levy + Fire + Health +
    Personal Levy × employees)
  - Market Levy (per-table, user enters a table number for each table)
  - Barrier Payment (Livestock: Goat/Cow/Sheep, Timber: Log/Plank, Opaque Beer:
    Tonne/Drum/20L, Grain, Mast — all quantity × rate)
- **Property Rates** — looks up all unpaid properties for the dialer's phone number,
  lets them pay one, or "All" combined.
- **License** — Liquor/Hunting/Trading, looked up by license number.
- **Permits** — Work/Trading/Building, looked up by permit number.
- Every payable journey ends at a shared confirm → PIN → **send payment prompt, end
  session** → background status polling → SMS receipt pipeline (§2a), backed by a
  mock-or-live ZynlePay mobile money integration and a mock-or-live SMS gateway.
- **Session-drop resume** — if a session drops, redialling within 3 minutes offers
  "1. Resume / 2. Main Menu"; resuming restores the exact prompt the user was on.

## 2. Why the flows look the way they do (corrections vs. the source pptx)

The source deck (`USSD Menu Flow chart Design-2.pptx`) is a design sketch, not a
finished spec. A few gaps were filled in deliberately, and documented here so nobody
mistakes them for bugs:

1. **PIN added to every payment**, not just Property Rates/License/Permits. The deck
   only shows PIN entry for those three; Levies just said "1. Confirm payment". Charging
   a mobile-money wallet without an authentication step on *any* path is a security
   gap, so every payable journey now goes through the same PIN gate. The PIN is set
   once at registration.
2. **PIN lockout**: 3 wrong attempts locks the PIN for 5 minutes (not in the deck at
   all). Without this, a stolen/borrowed phone gives unlimited PIN guesses.
3. **Registration always precedes any service**, exactly as slide 1 says
   ("Is Registered → NO → KYC"), but the deck's KYC confirmation summary shows
   Market/Shop No/Location fields with no earlier step collecting them — those three
   prompts were added (all skippable with `0` for ratepayers who aren't market
   vendors).
4. **USSD input convention**: real aggregators send the *entire* dialled string in
   `MESSAGE` (e.g. `262*22*1*1*2`), not just the newest digit. The controller here
   follows the same convention as `altusMiddleware`'s `UssdController` — take only the
   final `*`-separated segment as the actual input for this request.
5. **"Grain Levy" and "Mast Levy"** appear in the barrier-payment menu (slide 4) but
   the deck never diagrams their sub-flow. They're implemented as flat quantity × rate,
   consistent with every other barrier levy.
6. Session state amounts are computed from a `levy_rates` table, not hard-coded, so a
   council officer can retune fees without a code change.

## 2a. Payments are asynchronous — this matters

A mobile money collection is a **push**: the gateway sends a prompt to the
customer's phone, and the customer has to notice it and approve it — that can take
anywhere from a couple of seconds to a couple of minutes, and sometimes never
happens at all. A USSD session **cannot stay open** waiting for that; aggregators
time out sessions after a few seconds of inactivity regardless of what the app is
doing server-side.

So the flow is deliberately split into two halves:

1. **In the USSD session** (`PinEntryState` → `InitiatePaymentAction`): the PIN is
   verified, a `transactions` row is created (`status=pending`), and
   `PaymentGatewayService::initiate()` sends the push. The *only* thing checked
   synchronously is whether the gateway **accepted the request** — not whether the
   customer approved it. The session then ends immediately with
   `PaymentPendingState`: *"A payment prompt has been sent to your phone... you will
   receive an SMS confirming the result shortly."* If the gateway rejects the
   request outright (bad payload, gateway down), that's known instantly and
   `PaymentFailedState` is shown instead — no SMS involved at that point since
   nothing was sent to the customer.
2. **Out of band, on a queue worker** (`App\Jobs\CheckZynlePayPaymentJob`): starting
   `ZYNLEPAY_POLL_FIRST_DELAY` seconds after the push was sent, the job asks
   `PaymentGatewayService::pollStatus()` for the outcome. If it's still `pending`,
   the job throws, which triggers Laravel's normal queue retry/backoff — that *is*
   the polling loop, just expressed as retries instead of a blocking `sleep()`. It
   keeps trying every `ZYNLEPAY_POLL_INTERVAL` seconds, up to
   `ZYNLEPAY_POLL_MAX_ATTEMPTS` times (default 24 × 5s = **2 minutes**). Only once
   the gateway says `success` or `failed` — or the attempts are exhausted, treated
   as a timeout/failure — does the job update the `transactions` row and send the
   **one and only** SMS for that payment. A successful payment also marks the
   source property/license/permit row `paid` at this point, not before.

This is the same pattern `altusMiddleware` uses for its deposit push
(`DepositCollectionState` + `SendDepositPushJob`) — the important part isn't the
specific mechanism, it's that **the SMS only ever goes out after the gateway has
told us the real, final outcome**, never optimistically and never based on a
synchronous wait that can time out well before the customer has even looked at
their phone.

**This requires a queue worker to be running** — see §6a. Without one, the initial
push is still sent (that part is synchronous), but nothing will ever check its
outcome or send the confirmation SMS.

## 3. Architecture

```
app/Ussd/
  Actions/            StartAction (registration check), RegisterCustomerAction,
                       InitiatePaymentAction (sends the push, ends the session)
  States/
    Welcome/           WelcomeState (unregistered greeting)
    Registration/       full KYC wizard, one state per field
    MainMenuState
    Levies/
      LevyMenuState
      Business/         type → (name|renewal-lookup) → employees → cart
      Market/            table count → per-table number loop → cart
      Barrier/           type → sub-type (where applicable) → qty → cart
    PropertyRates/       lookup → select (or "All") → cart
    License/             type → number → cart
    Permit/               type → number → cart
    Shared/               ConfirmPaymentState, PinEntryState, PaymentPendingState,
                          PaymentFailedState — one generic pipeline every
                          journey above funnels into
    Errors/               GoodbyeState
  Support/               OptionMenuState, NumericInputState, TextInputState,
                          ErrorRetryTrait — generic base classes so "menu with
                          numbered options" / "enter a number" / "enter free
                          text" aren't reimplemented per-journey
app/Jobs/
  CheckZynlePayPaymentJob   background status polling — see §2a
```

**Why a generic "cart" pipeline?** Every journey (business levy, market levy, barrier
levy, property rates, license, permit) ends the same way: an itemised total, a PIN, a
gateway push, and (later, async) an SMS. Rather than duplicate that five times, each
journey's last Action writes `cart_title` / `cart_items` / `cart_total` /
`cart_category` / `cart_meta` / `cart_cancel_next` into the USSD record, and hands off
to the single `ConfirmPaymentState → PinEntryState → InitiatePaymentAction →
PaymentPendingState` chain (with `CheckZynlePayPaymentJob` finishing the job out of
band — see §2a). This is also why adding a brand new levy type is a ~20-line Action,
not a new confirm/pin/payment/receipt state set.

### Services

- `App\Services\PaymentGatewayService` — ZynlePay wrapper (mirrors
  `ModziPayMiddleware\ZynlePayHelper::momoDebit`/`checkPaymentStatus`), split into
  `initiate()` (send the push, synchronous, fast) and `pollStatus()` (ask for the
  outcome, called repeatedly from the background job — see §2a). Mock mode by
  default, including a `ZYNLEPAY_MOCK_PENDING_TICKS` knob to simulate a customer who
  takes a few polls to respond, for testing the retry loop itself.
- `App\Services\SmsService` — Zynle SMS wrapper (mirrors
  `ModziPayMiddleware\ZynleSMS::sendSMSNew`). Mock mode by default, every message
  (mocked or real) is logged to `sms_logs`.
- `App\Services\UssdResumeService` — see §5.

### Controller

`App\Http\Controllers\UssdController` follows the same shape as
`altusMiddleware\UssdController`: validates `auth.api_id`/`api_key`, extracts the
input from the aggregator's cumulative `MESSAGE` string, and drives
`Sparors\Ussd\Facades\Ussd::machine()`. It adds the resume gate (see §5) in front of
the machine call.

## 4. Data model

| Table | Purpose |
|---|---|
| `councils` | One row per council (Choma Council seeded); `ussd_shortcode`, SMS sender id |
| `ratepayers` | KYC record: name, gender, geography, market info, **hashed PIN**, lockout state |
| `business_levies` | Registered/renewed businesses, looked up on "Renewal" |
| `levy_rates` | Fee schedule — every levy amount in the system reads from here |
| `properties` | Property rates ledger: plot no, owner, balance b/f, current charge |
| `licenses` | License ledger: license no, holder, type, amount |
| `permits` | Permit ledger: permit no, holder, type, amount |
| `transactions` | Every payment attempt: reference, phone, category, itemised breakdown (JSON), amount, gateway status |
| `sms_logs` | Every SMS sent or mocked |
| `cache` | Laravel's database cache store — also where `sparors/laravel-ussd` keeps live session state, and where the resume snapshot lives |

## 5. Session-drop resume — how it actually works

`sparors/laravel-ussd` keys all session state as cache rows
`ussd_{SESSION_ID}.{key}`. A dropped session almost always gets a **new**
`SESSION_ID` on redial, so that state is orphaned by design. `UssdResumeService`
works around this without touching the package:

1. **After every non-terminal machine run**, the controller calls
   `snapshot($sessionId, $phone)`, which copies every `cache` row whose key contains
   `ussd_{sessionId}.` into one row keyed `ussd_resume.{phone}` (raw serialized
   values, copied byte-for-byte — no re-serialization risk), with a
   **3 minute TTL** (`USSD_RESUME_TTL`, config `ussdgateway.resume_ttl_seconds`).
2. **On a session's first request** (`MESSAGE` is bare, no prior input), if a resume
   snapshot exists for that phone, the controller — *before* touching the ussd
   machine at all — replies `"1. Resume / 2. Main Menu"` and remembers (via a
   short-lived `ussd_gate.{sessionId}` cache flag) that this session is mid-gate.
3. On the next request:
   - **`1` (Resume)** → every snapshotted row is rewritten under the *new*
     `SESSION_ID`, the previously-active state class is re-instantiated and
     `render()`-ed directly (no `next()` — we're re-showing the same prompt, not
     advancing past it), and the snapshot is refreshed.
   - **anything else** → the snapshot is discarded and the controller runs the
     machine fresh, exactly like a brand new dial (→ StartAction → KYC or Main Menu).
4. **On a terminal response** (receipt, goodbye, payment failed) the snapshot is
   cleared — there's nothing to resume once a journey has actually finished.

This relies on the `database` cache store (`CACHE_STORE=database`,
`USSD_STORE=database`) so the raw rows can be read/rewritten directly; it would need
adapting (or swapping to a Redis-backed variant) if a different cache backend is used
in production. **Redis is available via Docker on this machine** if you'd rather run
the USSD cache store on Redis for lower-latency production traffic — set
`CACHE_STORE=redis` and `USSD_STORE=redis`, but note `UssdResumeService` would need a
Redis-native rewrite (`SCAN`/`GETSET` instead of the `cache` table queries) since it
currently assumes SQL rows.

## 6. Configuration — what to change before going live

| File | Setting | What it's for |
|---|---|---|
| `.env` | `USSD_GATEWAY_API_ID` / `USSD_GATEWAY_API_KEY` | Credentials the aggregator must send in `auth` |
| `.env` | `USSD_SHORTCODES` | Comma-separated dial codes this app answers, e.g. `262*22` for `*262*22#` |
| `.env` | `ZYNLEPAY_MOCK=false` + `ZYNLEPAY_MERCHANT_ID` / `ZYNLEPAY_API_ID` / `ZYNLEPAY_API_KEY` / `ZYNLEPAY_SERVICE_ID` / `ZYNLEPAY_STATUS_API_ID` / `ZYNLEPAY_STATUS_API_KEY` | Live ZynlePay collection credentials (see `ModziPayMiddleware/app/Helpers/ZynlePayHelper.php` for where these values come from operationally) |
| `.env` | `SMS_MOCK=false` + `SMS_SENDER_ID` / `SMS_API_KEY` / `SMS_CLIENT_ID` | Live Zynle SMS credentials |
| `.env` | `ZYNLEPAY_POLL_FIRST_DELAY` / `ZYNLEPAY_POLL_MAX_ATTEMPTS` / `ZYNLEPAY_POLL_INTERVAL` | How long/often the background job checks for the customer's approval (default: first check after 5s, every 5s after, up to 24 times = 2 minutes) |
| `.env` | `USSD_TEST_LOW_RATES` | **Set to `false` before going live** — `true` collapses every fee to `USSD_TEST_RATE_AMOUNT` for cheap live-payment smoke testing (see §7a) |
| `.env` | `DB_CONNECTION` etc. | Switch from SQLite to MySQL for production — no code changes needed, Eloquent/migrations are DB-agnostic |
| `database/seeders/LevyRateSeeder.php` | levy amounts | Council-specific fee schedule |
| `database/seeders/CouncilSeeder.php` | council name/shortcode | Per-council identity |
| `config/zambia.php` | provinces/districts/constituencies/wards | Extend beyond the Choma pilot detail |

**Nothing else needs code changes to onboard a new council** — new fee schedule, new
seed data, new `.env` credentials.

## 6a. Running the queue worker (required)

Since §2a's `CheckZynlePayPaymentJob` runs on Laravel's queue, **a worker process
must be running at all times** in every environment (local testing included) or
payment outcomes will never be checked and no confirmation SMS will ever be sent —
the initial push still goes out, but nothing follows up on it.

```bash
php artisan queue:work
```

- **Local testing**: run the above in its own terminal alongside `php artisan serve`.
- **Production**: supervise it (e.g. Supervisor, systemd, or Laravel Forge's built-in
  queue worker management) so it restarts automatically if it dies, and restart it
  after every deploy (`php artisan queue:restart` picks up new code without needing
  to kill the process manually).
- **Important**: a running worker reads `.env`/config **once at boot**. If you change
  `.env` (e.g. flipping `ZYNLEPAY_MOCK_OUTCOME` while testing, or any other setting)
  the worker must be restarted to see it — `php artisan config:clear` alone is not
  enough for an already-running worker process.
- Uses `QUEUE_CONNECTION=database` (the `jobs`/`failed_jobs` tables from Laravel's
  default migrations) so no extra infrastructure is needed for this prototype; swap
  to Redis (`QUEUE_CONNECTION=redis`, available via Docker on this machine) for
  higher-throughput production traffic — no code changes needed, only config.

## 7. Test/demo data (SQLite, seeded via `php artisan migrate:fresh --seed`)

All registered ratepayers share PIN **`1234`**.

| Phone | Ratepayer | What it's for |
|---|---|---|
| `260977000001` | William Phiri | Market vendor — Market Levy, Barrier Levy |
| `260977000002` | Oxylane Digital Solutions Ltd | Business Levy renewal (existing `business_levies` row, 10 employees) |
| `260977000003` | Kumawa Farms Ltd | Property Rates — 2 unpaid properties (`2604B`, `0053HD`) |
| `260977000004` | Namwela Zanga | License — Liquor License `001234ZM`, ZMW 5,000 |
| `260977000005` | Juma Azizi | Permit — Work Permit `WP-2604`, ZMW 5,000 |
| `260977000006` | Mary Banda | Market Levy, fresh Business Levy "New application" |
| `260977000099` | *(unregistered)* | Exercises the full KYC registration journey |

Dial code: `885*228` (`USSD_SHORTCODES`/`USSD_DISPLAY_SHORTCODE` — maps to `*885*228#`).

## 7a. Testing with real money (`USSD_TEST_LOW_RATES`)

To smoke-test an actual mobile money payment without spending the real council fee
schedule, `.env` currently has:

```
USSD_TEST_LOW_RATES=true
USSD_TEST_RATE_AMOUNT=1
```

With this on (re-run `php artisan db:seed` after toggling it), every figure the
customer sees is **ZMW 1.00 flat** — including Business Levy, where the Fire/Health/
Personal Levy components are zeroed out rather than each becoming K1 and summing to
more than K1. Quantity-based levies (Market table, Barrier livestock/timber/beer/
grain/mast) are K1 **per unit**, so enter a quantity of 1 for the cheapest possible
test. **Set `USSD_TEST_LOW_RATES=false` and re-seed before going live** — it is not
safe to leave on in production.

## 8. Runtime verification performed

Every journey below was driven end-to-end with real `curl` POSTs against
`php artisan serve`, using the exact JSON shape the USSD gateway sends
(`auth.api_id`/`api_key`, `ussd_request.SESSION_ID`/`MSISDN`/`MESSAGE`/`OPERATOR`),
covering both good and bad input at each step:

- ✅ Registered dial-in → Main Menu (personalised greeting)
- ✅ Unregistered dial-in → full KYC wizard → registration success → re-dial → Main Menu
- ✅ Business Levy — New application (Levy 1,600 + Fire 350 + Health 580 + Personal
  50×n) and Renewal (500 + 150 + 320 + 50×n) — totals match the pptx worked examples
  exactly (e.g. renewal, 10 employees → ZMW 1,470.00)
- ✅ Market Levy — multi-table loop, itemised per table (2 tables → ZMW 10.00, matches
  pptx)
- ✅ Barrier Payment — Livestock (Cow ×4 → ZMW 400.00), Timber (Log ×20 → ZMW 300.00),
  Grain (Bag ×5 → ZMW 50.00)
- ✅ Property Rates — single property and "99. All" combined (ZMW 6,400.00, matches
  pptx's Kumawa Farms + Planet Autospares example)
- ✅ License — Liquor License lookup and payment (ZMW 5,000.00, matches pptx)
- ✅ Permit — Work Permit lookup and payment (ZMW 5,000.00, matches pptx)
- ✅ **Asynchronous payment pipeline** (§2a) — confirmed the session ends immediately
  at "A payment prompt has been sent to your phone..." (not a synchronous success/
  failure screen), and that `CheckZynlePayPaymentJob`, running on a real
  `php artisan queue:work` worker, resolves it afterwards:
  - ✅ Immediate success (`ZYNLEPAY_MOCK_OUTCOME=success`) — job runs once, marks the
    transaction `success`, marks the source record `paid`, sends exactly one SMS
  - ✅ Immediate failure (`ZYNLEPAY_MOCK_OUTCOME=failed`) — job runs once, marks
    `failed`, source record stays `unpaid`, "not completed" SMS sent
  - ✅ **Delayed approval** (`ZYNLEPAY_MOCK_PENDING_TICKS=2`) — watched the job report
    "still pending", retry with backoff twice, then resolve successfully on the 3rd
    poll — proving the retry/backoff loop itself works, not just the two instant
    outcomes above
  - ✅ **Timeout** (pending forever, `ZYNLEPAY_POLL_MAX_ATTEMPTS=3`) — watched all 3
    attempts report pending, the job's `failed()` handler fire, the transaction marked
    `failed` with `{"timeout":true}`, and a "not completed" SMS sent
  - ✅ Confirmed a running `queue:work` process does **not** pick up `.env` changes
    until restarted — documented as an operational gotcha in §6a
- ✅ Gateway rejects the push outright (immediate, synchronous) — `PaymentFailedState`
  shown right away, no SMS (nothing was ever sent to the customer)
- ✅ Cancel (`00`) at the confirm screen — returns to the levy menu, cart cleared
- ✅ PIN lockout — 3 wrong attempts locks the PIN for 5 minutes; a 4th attempt within
  the lockout window is rejected immediately without re-prompting
- ✅ Invalid menu options at every menu level — re-renders the same menu with an
  "Invalid option" banner, never crashes or loses state
- ✅ Non-numeric / out-of-range numeric input (employees, quantities) — rejected with a
  clear range message, state preserved
- ✅ Empty/invalid free-text input (names, license/permit numbers) — rejected,
  re-prompted
- ✅ Fully-paid ratepayer dialling Property Rates — "no outstanding property rates",
  session ends cleanly
- ✅ Unknown/already-paid license or permit number — "not found or already paid",
  re-prompted
- ✅ Unauthenticated gateway request (`auth.api_id`/`api_key` wrong) → `401`
- ✅ Malformed request (missing `SESSION_ID`) → graceful "Session error" terminal reply
- ✅ **Session-drop resume**: began a Barrier Payment journey on session A, simulated a
  drop, redialled on a brand-new session B → offered Resume, chose "1", and picked up
  exactly on the menu the user had left (not the main menu) — verified by then
  continuing the flow to a successful payment
- ✅ **Resume discard**: redialled after a drop, chose "2. Main Menu" → old session
  state discarded, fresh Main Menu shown (matches "if they decide to go to the main
  menu it clears the resume and starts afresh")

Every transaction and SMS above is inspectable in the `transactions` and `sms_logs`
tables — `breakdown` is stored as JSON so downstream reconciliation/backend
integration has the full itemised context, not just a total.

## 9. Known simplifications (prototype scope)

- Constituency/Ward reference data is fully realistic only for Choma District;
  other districts use a representative placeholder list (`config/zambia.php`) —
  sufficient to exercise every branch of the registration journey, not a full
  Zambian electoral gazette.
- The gateway aggregator's exact request/response envelope varies by provider; this
  implementation mirrors `altusMiddleware`'s convention (`auth`, `ussd_request` with
  `SESSION_ID`/`MSISDN`/`MESSAGE`/`OPERATOR`, response `ussd_response` with
  `USSD_BODY`/`REQUEST_TYPE`). Adjust `UssdController::handle()` if your production
  aggregator's envelope differs — the state machine layer underneath needs no
  changes.
- `QUEUE_CONNECTION=database` — simple and dependency-free for this prototype, but a
  worker must be kept running at all times (see §6a). Redis is a drop-in upgrade for
  production throughput if needed.

## 10. Running it

```bash
composer install
cp .env.example .env   # already pre-filled with mock-mode defaults for this prototype
php artisan key:generate
php artisan migrate:fresh --seed
php artisan serve --port=8123
php artisan queue:work   # in a second terminal — required, see §6a
```

Send a USSD request:

```bash
curl -s -X POST http://127.0.0.1:8123/ussd -H "Content-Type: application/json" -d '{
  "auth": {"api_id": "1234", "api_key": "1234"},
  "ussd_request": {
    "SESSION_ID": "demo1",
    "MSISDN": "260977000001",
    "MESSAGE": "885*228",
    "OPERATOR": "MTN"
  }
}'
```

Then continue the same session by re-sending the *entire* dialled string so far in
`MESSAGE` (e.g. `885*228*1`, then `885*228*1*1`, …) with the same `SESSION_ID`.
