<?php

namespace App\Ussd\States\Permit;

use App\Ussd\Support\TextInputState;

class PermitNumberState extends TextInputState
{
    protected function prompt(): string
    {
        return 'Enter Permit No.:';
    }

    protected function recordKey(): string
    {
        return 'permit_no';
    }

    protected function nextState(): string
    {
        return BuildPermitCartAction::class;
    }

    protected function normalize(string $value): string
    {
        return strtoupper(trim($value));
    }
}
