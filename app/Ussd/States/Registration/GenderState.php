<?php

namespace App\Ussd\States\Registration;

use App\Ussd\Support\OptionMenuState;

class GenderState extends OptionMenuState
{
    protected function title(): string
    {
        return 'Select your Gender:';
    }

    protected function options(): array
    {
        return [
            '1' => ['label' => 'Male', 'next' => ProvinceState::class, 'set' => ['reg_gender' => 'male']],
            '2' => ['label' => 'Female', 'next' => ProvinceState::class, 'set' => ['reg_gender' => 'female']],
        ];
    }
}
