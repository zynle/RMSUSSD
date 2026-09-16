<?php

namespace App\Ussd\States\Permit;

use App\Ussd\States\MainMenuState;
use App\Ussd\Support\OptionMenuState;

class PermitTypeState extends OptionMenuState
{
    protected function title(): string
    {
        return 'Please select Permit:';
    }

    protected function options(): array
    {
        return [
            '1' => ['label' => 'Work Permit', 'next' => PermitNumberState::class, 'set' => ['permit_type' => 'Work Permit']],
            '2' => ['label' => 'Trading Permit', 'next' => PermitNumberState::class, 'set' => ['permit_type' => 'Trading Permit']],
            '3' => ['label' => 'Building Permit', 'next' => PermitNumberState::class, 'set' => ['permit_type' => 'Building Permit']],
            '00' => ['label' => 'Back', 'next' => MainMenuState::class],
        ];
    }
}
