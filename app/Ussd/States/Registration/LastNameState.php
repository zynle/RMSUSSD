<?php

namespace App\Ussd\States\Registration;

use App\Ussd\Support\TextInputState;

class LastNameState extends TextInputState
{
    protected function prompt(): string
    {
        return "Please enter your\nLast Name:";
    }

    protected function recordKey(): string
    {
        return 'reg_last_name';
    }

    protected function nextState(): string
    {
        return GenderState::class;
    }

    protected function validate(string $value): ?string
    {
        $value = trim($value);
        if ($value === '') {
            return 'Last name cannot be empty.';
        }
        if (!preg_match("/^[a-zA-Z'\- ]{2,40}$/", $value)) {
            return 'Please enter a valid last name (letters only).';
        }

        return null;
    }

    protected function normalize(string $value): string
    {
        return ucwords(strtolower(trim($value)));
    }
}
