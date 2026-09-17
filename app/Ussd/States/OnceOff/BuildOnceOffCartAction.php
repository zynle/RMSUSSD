<?php

namespace App\Ussd\States\OnceOff;

use App\Models\LevyRate;
use App\Ussd\States\Shared\ConfirmPaymentState;
use Sparors\Ussd\Action;

class BuildOnceOffCartAction extends Action
{
    public function run(): string
    {
        $rateCode = (string) $this->record->get('once_off_rate_code');
        $rate = LevyRate::where('code', $rateCode)
            ->where('category', 'once_off')
            ->where('is_active', true)
            ->first();

        if (!$rate) {
            $this->record->set(
                OnceOffApplicationState::class . '_error',
                "This application is currently unavailable. Please select another option."
            );

            return OnceOffApplicationState::class;
        }

        $amount = (float) $rate->rate;

        $this->record->set('cart_title', strtoupper($rate->label));
        $this->record->set('cart_items', [
            ['label' => $rate->label, 'amount' => $amount],
        ]);
        $this->record->set('cart_total', $amount);
        $this->record->set('cart_category', 'once_off_application');
        $this->record->set('cart_meta', ['rate_code' => $rate->code, 'application' => $rate->label]);
        $this->record->set('cart_cancel_next', OnceOffApplicationState::class);
        $this->record->set('cart_requires_pin', false);
        $this->record->delete('once_off_rate_code');

        return ConfirmPaymentState::class;
    }
}
