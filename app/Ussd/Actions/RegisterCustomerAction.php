<?php

namespace App\Ussd\Actions;

use App\Models\Ratepayer;
use App\Ussd\States\Registration\RegistrationSuccessState;
use Sparors\Ussd\Action;

class RegisterCustomerAction extends Action
{
    private const REGISTRATION_KEYS = [
        'reg_last_name', 'reg_first_name', 'reg_gender', 'reg_province',
        'reg_district', 'reg_constituency', 'reg_ward',
        'reg_market_name', 'reg_shop_no', 'reg_location', 'reg_pin',
    ];

    public function run(): string
    {
        $phone = $this->record->get('phoneNumber');

        $ratepayer = Ratepayer::updateOrCreate(
            ['phone' => $phone],
            [
                'first_name' => $this->record->get('reg_first_name'),
                'last_name' => $this->record->get('reg_last_name'),
                'gender' => $this->record->get('reg_gender'),
                'province' => $this->record->get('reg_province'),
                'district' => $this->record->get('reg_district'),
                'constituency' => $this->record->get('reg_constituency'),
                'ward' => $this->record->get('reg_ward'),
                'market_name' => $this->record->get('reg_market_name') ?: null,
                'shop_no' => $this->record->get('reg_shop_no') ?: null,
                'location' => $this->record->get('reg_location'),
                'pin' => $this->record->get('reg_pin'),
                'is_registered' => true,
                'registered_at' => now(),
            ]
        );

        $this->record->deleteMultiple(self::REGISTRATION_KEYS);
        $this->record->set('ratepayer_name', $ratepayer->fullName());

        app(\App\Services\SmsService::class)->send(
            $phone,
            "Welcome {$ratepayer->fullName()}! You are now registered on the Choma Council Digital Platform. Dial the USSD code any time to pay levies, rates, licenses and permits."
        );

        return RegistrationSuccessState::class;
    }
}
