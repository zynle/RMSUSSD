<?php

namespace App\Ussd\States\Levies\Barrier;

use App\Ussd\Support\OptionMenuState;

class TimberTypeState extends OptionMenuState
{
    protected function title(): string
    {
        return 'Select Timber type:';
    }

    protected function options(): array
    {
        return [
            '1' => ['label' => 'Per Log', 'next' => BarrierQtyState::class, 'set' => [
                'barrier_rate_code' => 'barrier_timber_log',
                'barrier_label' => 'Timber Levy - Logs',
                'barrier_unit_label' => 'Logs',
            ]],
            '2' => ['label' => 'Per Plank', 'next' => BarrierQtyState::class, 'set' => [
                'barrier_rate_code' => 'barrier_timber_plank',
                'barrier_label' => 'Timber Levy - Planks',
                'barrier_unit_label' => 'Planks',
            ]],
            '00' => ['label' => 'Back', 'next' => BarrierTypeState::class],
        ];
    }
}
