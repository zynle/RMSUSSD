<?php

namespace App\Ussd\States\Registration;

use App\Ussd\Support\TextInputState;

class MarketNameState extends TextInputState
{
    protected function prompt(): string
    {
        return "If you trade at a Council market, enter the Market Name.\nOtherwise enter 0 to skip:";
    }

    protected function recordKey(): string
    {
        return 'reg_market_name';
    }

    protected function nextState(): string
    {
        return ShopNoState::class;
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
