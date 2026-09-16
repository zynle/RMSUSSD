<?php

namespace App\Ussd\States\Levies\Business;

use App\Ussd\Support\NumericInputState;

class BusinessEmployeesState extends NumericInputState
{
    protected function prompt(): string
    {
        return 'Enter number of Employees:';
    }

    protected function recordKey(): string
    {
        return 'business_employees';
    }

    protected function nextState(): string
    {
        return BuildBusinessLevyCartAction::class;
    }

    protected function min(): int
    {
        return 1;
    }

    protected function max(): int
    {
        return 500;
    }
}
