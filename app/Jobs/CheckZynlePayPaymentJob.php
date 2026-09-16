<?php

namespace App\Jobs;

use App\Models\LicenseRecord;
use App\Models\PermitRecord;
use App\Models\PropertyRecord;
use App\Models\Transaction;
use App\Services\PaymentGatewayService;
use App\Services\SmsService;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Polls ZynlePay for the outcome of a mobile money push that was already
 * sent to the customer's phone. The USSD session that started this
 * payment has already ended (see App\Ussd\Actions\InitiatePaymentAction)
 * — this job runs entirely out of band, on a queue worker, and is the
 * ONLY place a success/failure SMS is sent from for an accepted push.
 *
 * Polling uses an exponential-then-plateau schedule (config
 * zynlepay.poll_schedule_seconds — default 10s, 10s, 15s, 20s, 25s, 30s,
 * 40s, 50s, 60s, 60s, then every 120s) rather than a fixed interval, so a
 * customer who approves quickly gets confirmed quickly, while one who
 * takes longer isn't hammered with requests every few seconds. The whole
 * thing is capped by retryUntil() — a hard wall-clock window (default 10
 * minutes, zynlepay.poll_total_window_minutes) after which failed() fires
 * regardless of how many polls happened, treated as a timeout/failure. An
 * early success or failure from the gateway closes it out immediately —
 * it never waits out the rest of the window once resolved.
 */
class CheckZynlePayPaymentJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public int $transactionId)
    {
    }

    public static function firstDelaySeconds(): int
    {
        return (int) (static::schedule()[0] ?? 10);
    }

    protected static function schedule(): array
    {
        return array_map('intval', config('zynlepay.poll_schedule_seconds', [10, 10, 15, 20, 25, 30, 40, 50, 60, 60, 120]));
    }

    /**
     * Laravel reuses the last array value for every attempt beyond the
     * array's length, which is exactly the "plateau" behaviour we want.
     */
    public function backoff(): array
    {
        return static::schedule();
    }

    /**
     * Hard cap on total wall-clock time since this job first became
     * available, independent of how many attempts the schedule above
     * produces in that window.
     */
    public function retryUntil(): Carbon
    {
        return now()->addMinutes((int) config('zynlepay.poll_total_window_minutes', 10));
    }

    public function handle(PaymentGatewayService $gateway, SmsService $sms): void
    {
        $transaction = Transaction::find($this->transactionId);

        // Already resolved (e.g. a previous attempt of this same job got
        // the final answer right before a retry fired) or gone — nothing
        // to do.
        if (!$transaction || $transaction->status !== 'pending') {
            return;
        }

        $result = $gateway->pollStatus($transaction->reference);

        if ($result['status'] === 'pending') {
            Log::debug("Payment {$transaction->reference} still pending (attempt {$this->attempts()}, elapsed since dispatch: " . $transaction->created_at->diffForHumans(null, true) . ')');

            // Throwing triggers Laravel's retry/backoff schedule above —
            // this IS the polling loop, expressed as queue retries
            // instead of a blocking sleep().
            throw new \RuntimeException("Payment {$transaction->reference} still pending customer approval.");
        }

        $this->resolve($transaction, $result['status'], $result['gateway_reference'], $result['response']);
    }

    /**
     * The full polling window (retryUntil) elapsed with the gateway still
     * saying "pending" every time — the customer never approved (or
     * rejected) the prompt in time.
     */
    public function failed(\Throwable $e): void
    {
        $transaction = Transaction::find($this->transactionId);

        if (!$transaction || $transaction->status !== 'pending') {
            return;
        }

        Log::warning("Payment {$transaction->reference} timed out waiting for customer approval after " . config('zynlepay.poll_total_window_minutes', 10) . ' minutes.');

        $this->resolve($transaction, 'failed', null, ['timeout' => true]);
    }

    protected function resolve(Transaction $transaction, string $status, ?string $gatewayReference, mixed $rawResponse): void
    {
        $transaction->update([
            'status' => $status,
            'gateway_reference' => $gatewayReference,
            'gateway_response' => json_encode($rawResponse),
            'completed_at' => now(),
        ]);

        $title = $transaction->breakdown['title'] ?? 'your payment';
        $phone = $transaction->phone;
        $amount = number_format((float) $transaction->amount, 2);

        $sms = app(SmsService::class);

        if ($status === 'success') {
            $this->markSourceRecordsPaid($transaction->service_category, $transaction->breakdown['meta'] ?? []);

            $sms->send(
                $phone,
                "Payment of ZMW {$amount} for {$title} was successful. Ref: {$transaction->reference}. Thank you."
            );

            return;
        }

        $sms->send(
            $phone,
            "Your payment of ZMW {$amount} for {$title} was not completed. Ref: {$transaction->reference}. Please dial the USSD code to try again."
        );
    }

    protected function markSourceRecordsPaid(string $category, array $meta): void
    {
        if ($category === 'property_rates' && !empty($meta['plot_nos'])) {
            PropertyRecord::whereIn('plot_no', $meta['plot_nos'])->update(['status' => 'paid']);
        }

        if ($category === 'license' && !empty($meta['license_no'])) {
            LicenseRecord::where('license_no', $meta['license_no'])->update(['status' => 'paid']);
        }

        if ($category === 'permit' && !empty($meta['permit_no'])) {
            PermitRecord::where('permit_no', $meta['permit_no'])->update(['status' => 'paid']);
        }
    }
}
