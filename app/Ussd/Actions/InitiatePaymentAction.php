<?php

namespace App\Ussd\Actions;

use App\Jobs\CheckZynlePayPaymentJob;
use App\Models\Transaction;
use App\Services\PaymentGatewayService;
use App\Ussd\States\Shared\PaymentFailedState;
use App\Ussd\States\Shared\PaymentPendingState;
use Illuminate\Support\Str;
use Sparors\Ussd\Action;

/**
 * Sends the mobile money push and ends the USSD session immediately —
 * exactly like a real council/bank USSD flow: the session cannot stay
 * open waiting for the customer to approve a prompt on their phone
 * (that can take anywhere from a few seconds to a couple of minutes).
 *
 * Whether it actually succeeds is determined afterwards, out of band, by
 * App\Jobs\CheckZynlePayPaymentJob — which is also the only place the
 * success/failure SMS is sent from. This action only reports whether the
 * gateway *accepted the request* (PaymentPendingState), or rejected it
 * outright (PaymentFailedState) — e.g. malformed request, gateway down.
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

        $gateway = app(PaymentGatewayService::class);
        $result = $gateway->initiate($phone, $total, $reference);

        if (!$result['accepted']) {
            $transaction->update([
                'status' => 'failed',
                'gateway_response' => json_encode($result['response']),
                'completed_at' => now(),
            ]);

            $this->record->set('failure_title', $title);
            $this->record->set('failure_reference', $reference);

            return PaymentFailedState::class;
        }

        CheckZynlePayPaymentJob::dispatch($transaction->id)
            ->delay(now()->addSeconds((int) config('zynlepay.poll_first_delay_seconds', 5)));

        $this->record->set('pending_title', $title);
        $this->record->set('pending_amount', $total);
        $this->record->set('pending_reference', $reference);

        return PaymentPendingState::class;
    }
}
