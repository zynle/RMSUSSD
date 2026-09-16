<?php

namespace App\Ussd\States\Registration;

use Sparors\Ussd\State;

class RegistrationSuccessState extends State
{
    protected $action = self::PROMPT;

    protected function beforeRendering(): void
    {
        $this->menu
            ->line('Registration successful!')
            ->lineBreak()
            ->line('To access services, dial the USSD code again:')
            ->text('*262*22#');
    }

    protected function afterRendering(string $argument): void
    {
        $this->decision->any(self::class);
    }
}
