<?php

namespace App\Ussd\States\Registration;

use App\Ussd\Support\TextInputState;

class ShopNoState extends TextInputState
{
    protected function prompt(): string
    {
        return "Enter your Shop/Stand No.\nOtherwise enter 0 to skip:";
    }

    protected function recordKey(): string
    {
        return 'reg_shop_no';
    }

    protected function nextState(): string
    {
        return LocationState::class;
    }

    protected function validate(string $value): ?string
    {
        return trim($value) === '' ? 'This field cannot be empty. Enter 0 to skip.' : null;
    }

    protected function normalize(string $value): string
    {
        $value = trim($value);

        return $value === '0' ? '' : $value;
    }
}
