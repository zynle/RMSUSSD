<?php

namespace App\Ussd\States\PropertyRates;

use App\Models\PropertyRecord;
use App\Ussd\States\MainMenuState;
use App\Ussd\States\Shared\ConfirmPaymentState;
use Sparors\Ussd\Action;

class BuildPropertyCartAction extends Action
{
    public function run(): string
    {
        $plotNos = $this->record->get('property_selected_plot_nos', []);
        $properties = PropertyRecord::whereIn('plot_no', $plotNos)->get();

        $items = [];
        $total = 0.0;

        foreach ($properties as $property) {
            $due = $property->totalDue();
            $items[] = ['label' => "{$property->owner_name} (Plot {$property->plot_no})", 'amount' => $due];
            $total += $due;
        }

        $this->record->set('cart_title', 'PROPERTY RATES');
        $this->record->set('cart_items', $items);
        $this->record->set('cart_total', $total);
        $this->record->set('cart_category', 'property_rates');
        $this->record->set('cart_meta', ['plot_nos' => $plotNos]);
        $this->record->set('cart_cancel_next', MainMenuState::class);

        $this->record->deleteMultiple(['property_plot_nos', 'property_selected_plot_nos']);

        return ConfirmPaymentState::class;
    }
}
