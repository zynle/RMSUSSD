<?php

namespace App\Ussd\States\Levies\Barrier;

use App\Models\LevyRate;
use App\Ussd\States\Levies\LevyMenuState;
use App\Ussd\States\Shared\ConfirmPaymentState;
use Sparors\Ussd\Action;

class BuildBarrierLevyCartAction extends Action
{
    public function run(): string
    {
        $code = $this->record->get('barrier_rate_code');
        $label = $this->record->get('barrier_label', 'Barrier Levy');
        $unitLabel = $this->record->get('barrier_unit_label', 'Units');
        $qty = (int) $this->record->get('barrier_qty', 0);

        $rate = LevyRate::rate($code);
        $total = $rate * $qty;

        $this->record->set('cart_title', strtoupper($label));
        $this->record->set('cart_items', [
            ['label' => "{$label} (ZMW " . number_format($rate, 2) . " x {$qty} {$unitLabel})", 'amount' => $total],
        ]);
        $this->record->set('cart_total', $total);
        $this->record->set('cart_category', 'barrier_levy');
        $this->record->set('cart_meta', ['code' => $code, 'label' => $label, 'qty' => $qty, 'rate' => $rate]);
        $this->record->set('cart_cancel_next', LevyMenuState::class);

        $this->record->deleteMultiple(['barrier_rate_code', 'barrier_label', 'barrier_unit_label', 'barrier_qty']);

        return ConfirmPaymentState::class;
    }
}
