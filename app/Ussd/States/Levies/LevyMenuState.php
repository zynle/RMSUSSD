<?php

namespace App\Ussd\States\Levies;

use App\Ussd\States\Levies\Barrier\BarrierTypeState;
use App\Ussd\States\Levies\Business\BusinessTypeState;
use App\Ussd\States\Levies\Market\MarketTableCountState;
use App\Ussd\States\MainMenuState;
use App\Ussd\Support\OptionMenuState;

class LevyMenuState extends OptionMenuState
{
    protected function title(): string
    {
        return 'Please select Levy:';
    }

    protected function options(): array
    {
        return [
            '1' => ['label' => 'Business Levy', 'next' => BusinessTypeState::class],
            '2' => ['label' => 'Market Levy', 'next' => MarketTableCountState::class],
            '3' => ['label' => 'Barrier Payment', 'next' => BarrierTypeState::class],
            '00' => ['label' => 'Back', 'next' => MainMenuState::class],
        ];
    }
}
