<?php

namespace App\Ussd\States\Levies\Barrier;

use App\Ussd\Support\OptionMenuState;

class LivestockTypeState extends OptionMenuState
{
    protected function title(): string
    {
        return 'Select Livestock type:';
    }

    protected function options(): array
    {
        return [
            '1' => ['label' => 'Goat Levy', 'next' => BarrierQtyState::class, 'set' => [
                'barrier_rate_code' => 'barrier_livestock_goat',
                'barrier_label' => 'Livestock Levy - Goat',
                'barrier_unit_label' => 'Goats',
            ]],
            '2' => ['label' => 'Cow Levy', 'next' => BarrierQtyState::class, 'set' => [
                'barrier_rate_code' => 'barrier_livestock_cow',
                'barrier_label' => 'Livestock Levy - Cow',
                'barrier_unit_label' => 'Cows',
            ]],
            '3' => ['label' => 'Sheep Levy', 'next' => BarrierQtyState::class, 'set' => [
                'barrier_rate_code' => 'barrier_livestock_sheep',
                'barrier_label' => 'Livestock Levy - Sheep',
                'barrier_unit_label' => 'Sheep',
            ]],
            '00' => ['label' => 'Back', 'next' => BarrierTypeState::class],
        ];
    }
}
