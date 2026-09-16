<?php

namespace App\Ussd\States\Registration;

use App\Ussd\Support\TextInputState;

class FirstNameState extends TextInputState
{
    protected function prompt(): string
    {
        return "Please enter your\nFirst Name:";
    }

    protected function recordKey(): string
    {
        return 'reg_first_name';
    }

    protected function nextState(): string
    {
        return LastNameState::class;
    }

    protected function validate(string $value): ?string
    {
        $value = trim($value);
        if ($value === '') {
            return 'First name cannot be empty.';
        }
        if (!preg_match("/^[a-zA-Z'\- ]{2,40}$/", $value)) {
            return 'Please enter a valid first name (letters only).';
        }

        return null;
    }

    protected function normalize(string $value): string
    {
        return ucwords(strtolower(trim($value)));
    }
}
