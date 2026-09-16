<?php

namespace App\Ussd\States\Shared;

use App\Ussd\Support\Money;
use Sparors\Ussd\State;

class ReceiptState extends State
{
    protected $action = self::PROMPT;

    protected function beforeRendering(): void
    {
        $title = $this->record->get('receipt_title', 'Payment');
        $amount = (float) $this->record->get('receipt_amount', 0);
        $reference = $this->record->get('receipt_reference', '-');

        $this->record->deleteMultiple(['receipt_title', 'receipt_amount', 'receipt_reference']);

        $this->menu
            ->line('Payment successful.')
            ->lineBreak()
            ->line($title)
            ->line('Amount: ' . Money::fmt($amount))
            ->line("Ref: {$reference}")
            ->lineBreak()
            ->text('An SMS confirmation has been sent to you. Thank you for using Choma Council services.');
    }

    protected function afterRendering(string $argument): void
    {
        $this->decision->any(self::class);
    }
}
