<?php

namespace App\Ussd\States\Levies\Barrier;

use App\Ussd\Support\NumericInputState;

class BarrierQtyState extends NumericInputState
{
    protected function prompt(): string
    {
        $unit = $this->record->get('barrier_unit_label', 'Units');

        return "Enter number of {$unit}:";
    }

    protected function recordKey(): string
    {
        return 'barrier_qty';
    }

    protected function nextState(): string
    {
        return BuildBarrierLevyCartAction::class;
    }

    protected function max(): int
    {
        return 5000;
    }
}
