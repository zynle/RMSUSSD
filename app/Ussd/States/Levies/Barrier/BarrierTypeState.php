<?php

namespace App\Ussd\States\Levies\Barrier;

use App\Ussd\States\Levies\LevyMenuState;
use App\Ussd\Support\OptionMenuState;

class BarrierTypeState extends OptionMenuState
{
    protected function title(): string
    {
        return 'Select Barrier Payment type:';
    }

    protected function options(): array
    {
        return [
            '1' => ['label' => 'Livestock Levy', 'next' => LivestockTypeState::class],
            '2' => ['label' => 'Timber Levy', 'next' => TimberTypeState::class],
            '3' => ['label' => 'Opaque Beer Levy', 'next' => OpaqueBeerTypeState::class],
            '4' => ['label' => 'Grain Levy', 'next' => BarrierQtyState::class, 'set' => [
                'barrier_rate_code' => 'barrier_grain_bag',
                'barrier_label' => 'Grain Levy',
                'barrier_unit_label' => 'Bags',
            ]],
            '5' => ['label' => 'Mast Levy', 'next' => BarrierQtyState::class, 'set' => [
                'barrier_rate_code' => 'barrier_mast_unit',
                'barrier_label' => 'Mast Levy',
                'barrier_unit_label' => 'Masts',
            ]],
            '00' => ['label' => 'Back', 'next' => LevyMenuState::class],
        ];
    }
}
