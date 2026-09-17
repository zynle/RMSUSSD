<?php

namespace App\Ussd\Actions;

use App\Models\Ratepayer;
use App\Ussd\States\Registration\RegistrationSuccessState;
use Illuminate\Support\Facades\Log;
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
        $phone = $this->normalizePhone((string) $this->record->get('phoneNumber'));
        $this->record->set('phoneNumber', $phone);
        $isNewRegistration = !Ratepayer::where('phone', $phone)->where('is_registered', true)->exists();

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

        Log::info(($isNewRegistration ? 'New ratepayer registered' : 'Ratepayer re-registered/updated') . ": #{$ratepayer->id} {$ratepayer->fullName()}", [
            'ratepayer_id' => $ratepayer->id,
            'phone' => $phone,
            'district' => $ratepayer->district,
            'ward' => $ratepayer->ward,
        ]);

        app(\App\Services\SmsService::class)->send(
            $phone,
            "Welcome {$ratepayer->fullName()}! You are now registered on the Choma Council Digital Platform. Dial the USSD code any time to pay levies, rates, licenses and permits."
        );

        return RegistrationSuccessState::class;
    }

    protected function normalizePhone(string $phone): string
    {
        $phone = preg_replace('/\D/', '', $phone) ?? '';

        if (strlen($phone) === 9) {
            return "260{$phone}";
        }

        if (strlen($phone) === 10 && str_starts_with($phone, '0')) {
            return '26' . $phone;
        }

        return $phone;
    }
}
