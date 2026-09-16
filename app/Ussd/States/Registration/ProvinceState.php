<?php

namespace App\Ussd\States\Registration;

use App\Ussd\Support\OptionMenuState;

class ProvinceState extends OptionMenuState
{
    protected function title(): string
    {
        return 'Please select Province:';
    }

    protected function options(): array
    {
        $options = [];
        foreach (array_values(config('zambia.provinces')) as $i => $province) {
            $key = (string) ($i + 1);
            $options[$key] = [
                'label' => $province,
                'next' => DistrictState::class,
                'set' => ['reg_province' => $province],
            ];
        }

        return $options;
    }
}
