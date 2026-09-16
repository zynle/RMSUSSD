<?php

namespace App\Ussd\States\PropertyRates;

use App\Models\PropertyRecord;
use App\Ussd\States\Errors\GoodbyeState;
use Sparors\Ussd\Action;

class PropertyLookupAction extends Action
{
    public function run(): string
    {
        $phone = $this->record->get('phoneNumber');
        $properties = PropertyRecord::where('phone', $phone)->where('status', 'unpaid')->get();

        if ($properties->isEmpty()) {
            $this->record->set('goodbye_message', "You have no outstanding property rates.\n\nThank you for using the Choma Council USSD service.");

            return GoodbyeState::class;
        }

        $this->record->set('property_plot_nos', $properties->pluck('plot_no')->all());

        return PropertySelectState::class;
    }
}
