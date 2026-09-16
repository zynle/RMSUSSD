<?php

namespace App\Ussd\States\PropertyRates;

use App\Models\PropertyRecord;
use App\Ussd\States\MainMenuState;
use App\Ussd\Support\ErrorRetryTrait;
use App\Ussd\Support\Money;
use Sparors\Ussd\State;

class PropertySelectState extends State
{
    use ErrorRetryTrait;

    protected function properties()
    {
        $plotNos = $this->record->get('property_plot_nos', []);

        return PropertyRecord::whereIn('plot_no', $plotNos)->get();
    }

    protected function beforeRendering(): void
    {
        $error = $this->consumeError();

        if ($error) {
            $this->menu->text($error);

            return;
        }

        $this->menu->line('Please select property:')->lineBreak();

        foreach ($this->properties() as $property) {
            $this->menu->line("{$property->plot_no}. {$property->owner_name} - " . Money::fmt($property->totalDue()));
        }

        if ($this->properties()->count() > 1) {
            $this->menu->line('99. All');
        }

        $this->menu->text('00. Back');
    }

    protected function afterRendering(string $argument): void
    {
        if ($argument === '00') {
            $this->decision->any(MainMenuState::class);

            return;
        }

        $properties = $this->properties();

        if ($argument === '99' && $properties->count() > 1) {
            $this->record->set('property_selected_plot_nos', $properties->pluck('plot_no')->all());
            $this->decision->any(BuildPropertyCartAction::class);

            return;
        }

        $match = $properties->firstWhere('plot_no', $argument);

        if (!$match) {
            $this->fail($this->invalidMessage());

            return;
        }

        $this->record->set('property_selected_plot_nos', [$match->plot_no]);
        $this->decision->any(BuildPropertyCartAction::class);
    }

    protected function invalidMessage(): string
    {
        $lines = ['Invalid option.', '', 'Please select property:', ''];
        foreach ($this->properties() as $property) {
            $lines[] = "{$property->plot_no}. {$property->owner_name} - " . Money::fmt($property->totalDue());
        }
        if ($this->properties()->count() > 1) {
            $lines[] = '99. All';
        }
        $lines[] = '00. Back';

        return implode(PHP_EOL, $lines);
    }
}
