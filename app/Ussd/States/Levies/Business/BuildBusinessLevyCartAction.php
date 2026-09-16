<?php

namespace App\Ussd\States\Levies\Business;

use App\Models\BusinessLevy;
use App\Models\LevyRate;
use App\Ussd\States\Levies\LevyMenuState;
use App\Ussd\States\Shared\ConfirmPaymentState;
use Sparors\Ussd\Action;

class BuildBusinessLevyCartAction extends Action
{
    public function run(): string
    {
        $phone = $this->record->get('phoneNumber');
        $status = $this->record->get('business_status', 'new');
        $employees = (int) $this->record->get('business_employees', 0);
        $name = $this->record->get('business_name', 'Business');

        $isNew = $status === 'new';
        $levy = LevyRate::rate($isNew ? 'business_new_levy' : 'business_renewal_levy');
        $fire = LevyRate::rate($isNew ? 'business_new_fire' : 'business_renewal_fire');
        $health = LevyRate::rate($isNew ? 'business_new_health' : 'business_renewal_health');
        $personalRate = LevyRate::rate('business_personal_levy');
        $personal = $personalRate * $employees;

        $total = $levy + $fire + $health + $personal;

        BusinessLevy::updateOrCreate(
            ['phone' => $phone, 'business_name' => $name],
            ['status' => 'existing', 'employees_count' => $employees]
        );

        $this->record->set('cart_title', ($isNew ? 'NEW APPLICATION' : 'RENEWAL') . " - {$name}");
        $this->record->set('cart_items', [
            ['label' => 'Levy', 'amount' => $levy],
            ['label' => 'Fire', 'amount' => $fire],
            ['label' => 'Health', 'amount' => $health],
            ['label' => "Personal Levy (ZMW {$personalRate} x {$employees})", 'amount' => $personal],
        ]);
        $this->record->set('cart_total', $total);
        $this->record->set('cart_category', 'business_levy');
        $this->record->set('cart_meta', ['business_name' => $name, 'status' => $status, 'employees' => $employees]);
        $this->record->set('cart_cancel_next', LevyMenuState::class);

        return ConfirmPaymentState::class;
    }
}
