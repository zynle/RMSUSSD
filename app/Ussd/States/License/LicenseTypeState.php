<?php

namespace App\Ussd\States\License;

use App\Ussd\States\MainMenuState;
use App\Ussd\Support\OptionMenuState;

class LicenseTypeState extends OptionMenuState
{
    protected function title(): string
    {
        return 'Please select License:';
    }

    protected function options(): array
    {
        return [
            '1' => ['label' => 'Liquor License', 'next' => LicenseNumberState::class, 'set' => ['license_type' => 'Liquor License']],
            '2' => ['label' => 'Hunting License', 'next' => LicenseNumberState::class, 'set' => ['license_type' => 'Hunting License']],
            '3' => ['label' => 'Trading License', 'next' => LicenseNumberState::class, 'set' => ['license_type' => 'Trading License']],
            '00' => ['label' => 'Back', 'next' => MainMenuState::class],
        ];
    }
}
