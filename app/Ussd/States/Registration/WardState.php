<?php

namespace App\Ussd\States\Registration;

use App\Ussd\Support\OptionMenuState;

class WardState extends OptionMenuState
{
    protected function title(): string
    {
        return 'Please select Ward:';
    }

    protected function options(): array
    {
        $constituency = $this->record->get('reg_constituency');
        $wards = config("zambia.wards.{$constituency}") ?? config('zambia.wards.__default');

        $options = [];
        foreach (array_values($wards) as $i => $ward) {
            $key = (string) ($i + 1);
            $options[$key] = [
                'label' => $ward,
                'next' => MarketNameState::class,
                'set' => ['reg_ward' => $ward],
            ];
        }
        $options['00'] = ['label' => 'Back', 'next' => ConstituencyState::class];

        return $options;
    }
}
