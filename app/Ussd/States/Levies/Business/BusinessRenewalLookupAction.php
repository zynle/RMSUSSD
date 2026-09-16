<?php

namespace App\Ussd\States\Levies\Business;

use App\Models\BusinessLevy;
use Sparors\Ussd\Action;

class BusinessRenewalLookupAction extends Action
{
    public function run(): string
    {
        $phone = $this->record->get('phoneNumber');
        $business = BusinessLevy::where('phone', $phone)->where('status', 'existing')->latest()->first();

        if (!$business) {
            $this->record->set(
                BusinessTypeState::class . '_error',
                "No existing business levy account found for renewal.\n\nPlease select:\n1. New application\n2. Renewal"
            );

            return BusinessTypeState::class;
        }

        $this->record->set('business_name', $business->business_name);

        return BusinessEmployeesState::class;
    }
}
