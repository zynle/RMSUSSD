<?php

namespace App\Ussd\Actions;

use App\Models\LicenseRecord;
use App\Models\PermitRecord;
use App\Models\PropertyRecord;
use App\Models\Transaction;
use App\Services\PaymentGatewayService;
use App\Services\SmsService;
use App\Ussd\States\Shared\PaymentFailedState;
use App\Ussd\States\Shared\ReceiptState;
use Illuminate\Support\Str;
use Sparors\Ussd\Action;

class ProcessPaymentAction extends Action
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

        $gateway = app(PaymentGatewayService::class);
        $result = $gateway->collect($phone, $total, $reference);

        $transaction->update([
            'status' => $result['status'],
            'gateway_reference' => $result['gateway_reference'],
            'gateway_response' => json_encode($result['response']),
            'completed_at' => now(),
        ]);

        $this->record->deleteMultiple(['cart_title', 'cart_items', 'cart_total', 'cart_category', 'cart_meta', 'cart_cancel_next']);

        if ($result['status'] === 'success') {
            $this->markSourceRecordsPaid($category, $meta);

            $sms = app(SmsService::class);
            $sms->send(
                $phone,
                "Payment of ZMW " . number_format($total, 2) . " for {$title} was successful. Ref: {$reference}. Thank you."
            );

            $this->record->set('receipt_title', $title);
            $this->record->set('receipt_amount', $total);
            $this->record->set('receipt_reference', $reference);

            return ReceiptState::class;
        }

        $sms = app(SmsService::class);
        $sms->send(
            $phone,
            "Your payment of ZMW " . number_format($total, 2) . " for {$title} failed. Ref: {$reference}. Please try again."
        );

        $this->record->set('failure_title', $title);
        $this->record->set('failure_reference', $reference);

        return PaymentFailedState::class;
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
