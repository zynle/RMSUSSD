<?php

namespace App\Ussd\States\Levies\Barrier;

use App\Ussd\Support\OptionMenuState;

class OpaqueBeerTypeState extends OptionMenuState
{
    protected function title(): string
    {
        return 'Select packaging type:';
    }

    protected function options(): array
    {
        return [
            '1' => ['label' => 'Per Tone', 'next' => BarrierQtyState::class, 'set' => [
                'barrier_rate_code' => 'barrier_beer_tonne',
                'barrier_label' => 'Opaque Beer Levy - Tonnes',
                'barrier_unit_label' => 'Tonnes',
            ]],
            '2' => ['label' => 'Per Drum', 'next' => BarrierQtyState::class, 'set' => [
                'barrier_rate_code' => 'barrier_beer_drum',
                'barrier_label' => 'Opaque Beer Levy - Drums',
                'barrier_unit_label' => 'Drums',
            ]],
            '3' => ['label' => 'Per 20 Liter', 'next' => BarrierQtyState::class, 'set' => [
                'barrier_rate_code' => 'barrier_beer_20l',
                'barrier_label' => 'Opaque Beer Levy - 20L Containers',
                'barrier_unit_label' => '20 Liters',
            ]],
            '00' => ['label' => 'Back', 'next' => BarrierTypeState::class],
        ];
    }
}
