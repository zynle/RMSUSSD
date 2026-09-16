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
            ->line('Payment failed.')
            ->lineBreak()
            ->line("We could not process {$title} (Ref: {$reference}).")
            ->text('Please check your mobile money balance and try again by dialing the USSD code.');
    }

    protected function afterRendering(string $argument): void
    {
        $this->decision->any(self::class);
    }
}
