<?php

namespace App\Ussd\States\Shared;

use App\Models\Ratepayer;
use App\Ussd\States\Errors\GoodbyeState;
use App\Ussd\Support\ErrorRetryTrait;
use App\Ussd\Support\Money;
use Sparors\Ussd\State;

class PinEntryState extends State
{
    use ErrorRetryTrait;

    private const MAX_ATTEMPTS = 3;
    private const LOCKOUT_MINUTES = 5;

    protected function beforeRendering(): void
    {
        $error = $this->consumeError();

        if ($error) {
            $this->menu->text($error);

            return;
        }

        $total = (float) $this->record->get('cart_total', 0);

        $this->menu
            ->line('Please enter your PIN to complete the transaction of')
            ->text(Money::fmt($total));
    }

    protected function afterRendering(string $argument): void
    {
        $phone = $this->record->get('phoneNumber');
        $ratepayer = Ratepayer::where('phone', $phone)->first();

        if (!$ratepayer) {
            $this->record->set('goodbye_message', 'We could not verify your account. Please try again later.');
            $this->decision->any(GoodbyeState::class);

            return;
        }

        if ($ratepayer->isPinLocked()) {
            $this->record->deleteMultiple(['cart_title', 'cart_items', 'cart_total', 'cart_category', 'cart_meta']);
            $this->record->set(
                'goodbye_message',
                "Too many incorrect PIN attempts. Your PIN has been locked for " . self::LOCKOUT_MINUTES . " minutes."
            );
            $this->decision->any(GoodbyeState::class);

            return;
        }

        if (!preg_match('/^\d{4}$/', $argument) || !$ratepayer->checkPin($argument)) {
            $ratepayer->increment('pin_attempts');

            if ($ratepayer->pin_attempts >= self::MAX_ATTEMPTS) {
                $ratepayer->forceFill([
                    'pin_locked_until' => now()->addMinutes(self::LOCKOUT_MINUTES),
                    'pin_attempts' => 0,
                ])->save();

                $this->record->deleteMultiple(['cart_title', 'cart_items', 'cart_total', 'cart_category', 'cart_meta']);
                $this->record->set(
                    'goodbye_message',
                    "Too many incorrect PIN attempts. Your PIN has been locked for " . self::LOCKOUT_MINUTES . " minutes."
                );
                $this->decision->any(GoodbyeState::class);

                return;
            }

            $remaining = self::MAX_ATTEMPTS - $ratepayer->pin_attempts;
            $this->fail("Incorrect PIN. {$remaining} attempt(s) remaining.\n\nPlease enter your PIN to complete the transaction of\n" . Money::fmt((float) $this->record->get('cart_total', 0)));

            return;
        }

        $ratepayer->update(['pin_attempts' => 0, 'pin_locked_until' => null]);

        $this->decision->any(\App\Ussd\Actions\InitiatePaymentAction::class);
    }
}
