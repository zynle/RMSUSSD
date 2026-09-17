<?php

namespace App\Ussd\States;

use App\Ussd\States\Errors\GoodbyeState;
use App\Ussd\States\Levies\LevyMenuState;
use App\Ussd\States\License\LicenseTypeState;
use App\Ussd\States\OnceOff\OnceOffApplicationState;
use App\Ussd\States\Permit\PermitTypeState;
use App\Ussd\States\PropertyRates\PropertyLookupAction;
use App\Ussd\Support\OptionMenuState;

class MainMenuState extends OptionMenuState
{
    protected function title(): string
    {
        $name = $this->record->get('ratepayer_name');

        return $name
            ? "Local Government Digital Platform.\nWelcome, {$name}.\nSelect service"
            : "Local Government Digital Platform.\nSelect service";
    }

    protected function options(): array
    {
        return [
            '1' => ['label' => 'Levies', 'next' => LevyMenuState::class],
            '2' => ['label' => 'Property Rates', 'next' => PropertyLookupAction::class],
            '3' => ['label' => 'License', 'next' => LicenseTypeState::class],
            '4' => ['label' => 'Permits', 'next' => PermitTypeState::class],
            '5' => ['label' => 'Once Off Applications', 'next' => OnceOffApplicationState::class],
            '00' => ['label' => 'Exit', 'next' => GoodbyeState::class],
        ];
    }
}
