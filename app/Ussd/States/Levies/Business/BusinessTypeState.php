<?php

namespace App\Ussd\States\Levies\Business;

use App\Ussd\States\Levies\LevyMenuState;
use App\Ussd\Support\OptionMenuState;

class BusinessTypeState extends OptionMenuState
{
    protected function title(): string
    {
        return 'Please select:';
    }

    protected function options(): array
    {
        return [
            '1' => ['label' => 'New application', 'next' => BusinessNameState::class, 'set' => ['business_status' => 'new']],
            '2' => ['label' => 'Renewal', 'next' => BusinessRenewalLookupAction::class, 'set' => ['business_status' => 'existing']],
            '00' => ['label' => 'Back', 'next' => LevyMenuState::class],
        ];
    }
}
