<?php

namespace App\Ussd\States\Welcome;

use App\Ussd\States\Errors\GoodbyeState;
use App\Ussd\States\Registration\LastNameState;
use App\Ussd\Support\OptionMenuState;

class WelcomeState extends OptionMenuState
{
    protected function title(): string
    {
        return "Welcome to the Choma Council Digital Platform.\nYou are not yet registered.";
    }

    protected function options(): array
    {
        return [
            '1' => ['label' => 'Register now', 'next' => LastNameState::class],
            '00' => ['label' => 'Exit', 'next' => GoodbyeState::class],
        ];
    }
}
