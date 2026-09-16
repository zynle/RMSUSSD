<?php

namespace App\Ussd\Actions;

use App\Jobs\SendPaymentPushJob;
use App\Models\Transaction;
use App\Ussd\States\Shared\PaymentPendingState;
use Illuminate\Support\Str;
use Sparors\Ussd\Action;

/**
 * Ends the USSD session immediately and hands the actual payment work off
 * to the queue entirely — exactly like a real council/bank USSD flow: the
 * session cannot stay open waiting for a customer to approve a prompt on
 * their phone (that can take anywhere from a few seconds to several
 * minutes), and it must not tie up the PHP process/worker handling this
 * HTTP request while a network call to the payment gateway is in flight.
 *
 * The only work done here, in the request/response cycle, is a local DB
 * insert (the pending transaction) and enqueuing a job — both fast and
 * local, no external network calls. SendPaymentPushJob (queued) is what
 * actually calls the gateway to send the push; CheckZynlePayPaymentJob
 * (dispatched by it) is what polls for the outcome and sends the
 * success/failure SMS. See docs/README §2a.
 */
class InitiatePaymentAction extends Action
{
    public function run(): string
    {
        $phone = $this->record->get('phoneNumber');
        $sessionId = $this->record->get('sessionId');
        $total = (float) $this->record->get('cart_total', 0);
        $category = $this->record->get('cart_category', 'other');
        $items = $this->record->get('cart_items', []);
        $meta = $this->record->get('cart_meta', []);
        $title = $this->record->get('cart_title', 'PAYMENT');

        $reference = strtoupper('RMS' . now()->format('ymd') . Str::random(6));

        $transaction = Transaction::create([
            'reference' => $reference,
            'session_id' => $sessionId,
            'phone' => $phone,
            'service_category' => $category,
            'breakdown' => ['title' => $title, 'items' => $items, 'meta' => $meta],
            'amount' => $total,
            'payment_method' => 'mobile_money',
            'status' => 'pending',
        ]);

        $this->record->deleteMultiple(['cart_title', 'cart_items', 'cart_total', 'cart_category', 'cart_meta', 'cart_cancel_next']);

        SendPaymentPushJob::dispatch($transaction->id);

        $this->record->set('pending_title', $title);
        $this->record->set('pending_amount', $total);
        $this->record->set('pending_reference', $reference);

        return PaymentPendingState::class;
    }
}
