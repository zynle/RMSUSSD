<?php

namespace App\Ussd\States\OnceOff;

use App\Ussd\States\Welcome\WelcomeState;
use App\Ussd\Support\OptionMenuState;

class OnceOffApplicationState extends OptionMenuState
{
    protected function title(): string
    {
        return 'Once Off Applications:';
    }

    protected function options(): array
    {
        return [
            '1' => ['label' => 'Business Premises / Trading Licence', 'next' => BuildOnceOffCartAction::class, 'set' => ['once_off_rate_code' => 'once_off_business_premises']],
            '2' => ['label' => 'Building Plan Approval', 'next' => BuildOnceOffCartAction::class, 'set' => ['once_off_rate_code' => 'once_off_building_plan']],
            '3' => ['label' => 'Planning / Development Permission', 'next' => BuildOnceOffCartAction::class, 'set' => ['once_off_rate_code' => 'once_off_planning_permission']],
            '4' => ['label' => 'Change of Land Use', 'next' => BuildOnceOffCartAction::class, 'set' => ['once_off_rate_code' => 'once_off_change_land_use']],
            '5' => ['label' => 'Subdivision / Consolidation', 'next' => BuildOnceOffCartAction::class, 'set' => ['once_off_rate_code' => 'once_off_subdivision']],
            '6' => ['label' => 'Occupancy Certificate', 'next' => BuildOnceOffCartAction::class, 'set' => ['once_off_rate_code' => 'once_off_occupancy_certificate']],
            '7' => ['label' => 'Fire Safety Certificate', 'next' => BuildOnceOffCartAction::class, 'set' => ['once_off_rate_code' => 'once_off_fire_safety']],
            '8' => ['label' => 'Outdoor Advertising Permit', 'next' => BuildOnceOffCartAction::class, 'set' => ['once_off_rate_code' => 'once_off_outdoor_advertising']],
            '9' => ['label' => 'Event / Public Gathering Permit', 'next' => BuildOnceOffCartAction::class, 'set' => ['once_off_rate_code' => 'once_off_event_permit']],
            '10' => ['label' => 'Liquor Licence Application', 'next' => BuildOnceOffCartAction::class, 'set' => ['once_off_rate_code' => 'once_off_liquor_licence']],
            '11' => ['label' => 'Burial / Exhumation Application', 'next' => BuildOnceOffCartAction::class, 'set' => ['once_off_rate_code' => 'once_off_burial_exhumation']],
            '00' => ['label' => 'Back', 'next' => WelcomeState::class],
        ];
    }
}
