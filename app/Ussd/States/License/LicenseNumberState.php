<?php

namespace App\Ussd\States\License;

use App\Ussd\Support\TextInputState;

class LicenseNumberState extends TextInputState
{
    protected function prompt(): string
    {
        return 'Enter License No.:';
    }

    protected function recordKey(): string
    {
        return 'license_no';
    }

    protected function nextState(): string
    {
        return BuildLicenseCartAction::class;
    }

    protected function normalize(string $value): string
    {
        return strtoupper(trim($value));
    }
}
