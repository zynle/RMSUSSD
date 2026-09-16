<?php

namespace App\Ussd\States\Registration;

use App\Ussd\Support\OptionMenuState;

class DistrictState extends OptionMenuState
{
    protected function title(): string
    {
        return 'Please select District:';
    }

    protected function options(): array
    {
        $province = $this->record->get('reg_province');
        $districts = config("zambia.districts.{$province}", []);

        $options = [];
        foreach (array_values($districts) as $i => $district) {
            $key = (string) ($i + 1);
            $options[$key] = [
                'label' => $district,
                'next' => ConstituencyState::class,
                'set' => ['reg_district' => $district],
            ];
        }
        $options['00'] = ['label' => 'Back', 'next' => ProvinceState::class];

        return $options;
    }
}
