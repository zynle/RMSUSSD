<?php

namespace App\Ussd\States\Registration;

use App\Ussd\Support\OptionMenuState;

class ConstituencyState extends OptionMenuState
{
    protected function title(): string
    {
        return 'Please select Constituency:';
    }

    protected function options(): array
    {
        $district = $this->record->get('reg_district');
        $constituencies = config("zambia.constituencies.{$district}") ?? config('zambia.constituencies.__default');

        $options = [];
        foreach (array_values($constituencies) as $i => $constituency) {
            $key = (string) ($i + 1);
            $options[$key] = [
                'label' => $constituency,
                'next' => WardState::class,
                'set' => ['reg_constituency' => $constituency],
            ];
        }
        $options['00'] = ['label' => 'Back', 'next' => DistrictState::class];

        return $options;
    }
}
