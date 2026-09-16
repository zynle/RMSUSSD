<?php

namespace App\Ussd\States\Shared;

use Sparors\Ussd\State;

class PaymentFailedState extends State
{
    protected $action = self::PROMPT;

    protected function beforeRendering(): void
    {
        $title = $this->record->get('failure_title', 'your payment');
        $reference = $this->record->get('failure_reference', '-');

        $this->record->deleteMultiple(['failure_title', 'failure_reference']);

        $this->menu
            ->line('Payment could not be started.')
            ->lineBreak()
            ->line("We could not send a payment prompt for {$title} (Ref: {$reference}).")
            ->text('Please try again shortly by dialing the USSD code.');
    }

    protected function afterRendering(string $argument): void
    {
        $this->decision->any(self::class);
    }
}
