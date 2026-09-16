<?php

namespace App\Ussd\States\Registration;

use App\Ussd\Support\TextInputState;

class PinCreateState extends TextInputState
{
    protected function prompt(): string
    {
        return "Create a 4-digit PIN to secure your payments:";
    }

    protected function recordKey(): string
    {
        return 'reg_pin';
    }

    protected function nextState(): string
    {
        return PinConfirmState::class;
    }

    protected function validate(string $value): ?string
    {
        return preg_match('/^\d{4}$/', trim($value)) ? null : 'PIN must be exactly 4 digits.';
    }
}
