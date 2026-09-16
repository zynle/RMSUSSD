<?php

namespace App\Ussd\States\Levies\Business;

use App\Ussd\Support\TextInputState;

class BusinessNameState extends TextInputState
{
    protected function prompt(): string
    {
        return "Enter your Business Name:";
    }

    protected function recordKey(): string
    {
        return 'business_name';
    }

    protected function nextState(): string
    {
        return BusinessEmployeesState::class;
    }

    protected function validate(string $value): ?string
    {
        return trim($value) === '' ? 'Business name cannot be empty.' : null;
    }
}
