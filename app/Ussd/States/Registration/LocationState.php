<?php

namespace App\Ussd\States\Registration;

use App\Ussd\Support\TextInputState;

class LocationState extends TextInputState
{
    protected function prompt(): string
    {
        return 'Enter your Location (e.g. town/area):';
    }

    protected function recordKey(): string
    {
        return 'reg_location';
    }

    protected function nextState(): string
    {
        return PinCreateState::class;
    }

    protected function validate(string $value): ?string
    {
        return trim($value) === '' ? 'Location cannot be empty.' : null;
    }
}
