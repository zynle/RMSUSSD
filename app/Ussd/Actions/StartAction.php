<?php

namespace App\Ussd\Actions;

use App\Models\Ratepayer;
use App\Ussd\States\MainMenuState;
use App\Ussd\States\Welcome\WelcomeState;
use Sparors\Ussd\Action;

class StartAction extends Action
{
    public function run(): string
    {
        $phone = $this->normalizePhone((string) $this->record->get('phoneNumber'));
        $this->record->set('phoneNumber', $phone);

        $ratepayer = Ratepayer::where('phone', $phone)->where('is_registered', true)->first();

        // Records created before phone numbers were normalized at the HTTP
        // boundary may still use a local 0xxxxxxxxx format. Let their next
        // dial repair the stored value so they can access their account.
        if (!$ratepayer && str_starts_with($phone, '260') && strlen($phone) === 12) {
            $legacyPhone = '0' . substr($phone, 3);
            $ratepayer = Ratepayer::where('phone', $legacyPhone)->where('is_registered', true)->first();

            if ($ratepayer) {
                $ratepayer->update(['phone' => $phone]);
            }
        }

        if ($ratepayer) {
            $this->record->set('ratepayer_name', $ratepayer->fullName());

            return MainMenuState::class;
        }

        return WelcomeState::class;
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
