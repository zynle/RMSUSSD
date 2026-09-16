<?php

namespace App\Ussd\States\Shared;

use App\Ussd\Support\Money;
use Sparors\Ussd\State;

/**
 * Terminal screen shown the instant the mobile money push has been sent
 * — before we know whether the customer will approve it. The actual
 * outcome is confirmed later by SMS (see App\Jobs\CheckZynlePayPaymentJob).
 */
class PaymentPendingState extends State
{
    protected $action = self::PROMPT;

    protected function beforeRendering(): void
    {
        $title = $this->record->get('pending_title', 'your payment');
        $amount = (float) $this->record->get('pending_amount', 0);
        $reference = $this->record->get('pending_reference', '-');

        $this->record->deleteMultiple(['pending_title', 'pending_amount', 'pending_reference']);

        $this->menu
            ->line('A payment prompt for ' . Money::fmt($amount) . ' has been sent to your phone.')
            ->lineBreak()
            ->line("Please approve it to complete payment for {$title}.")
            ->line("Ref: {$reference}")
            ->lineBreak()
            ->text('You will receive an SMS confirming the result shortly.');
    }

    protected function afterRendering(string $argument): void
    {
        $this->decision->any(self::class);
    }
}
