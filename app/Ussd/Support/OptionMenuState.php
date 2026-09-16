<?php

namespace App\Ussd\Support;

use Sparors\Ussd\State;

/**
 * Generic numbered-menu state. Subclasses declare a title and a flat list
 * of options (each mapped to the next State class, with optional record
 * values to persist on selection). Handles invalid input by re-rendering
 * the same menu with an error banner — this single class replaces what
 * would otherwise be dozens of near-identical menu classes across the
 * Levies / Barrier Payment / KYC journeys.
 */
abstract class OptionMenuState extends State
{
    use ErrorRetryTrait;

    /** @return string */
    abstract protected function title(): string;

    /**
     * @return array<string, array{label: string, next: string, set?: array<string, mixed>}>
     */
    abstract protected function options(): array;

    protected function beforeRendering(): void
    {
        $error = $this->consumeError();

        if ($error) {
            $this->menu->text($error);

            return;
        }

        $this->menu->line($this->title())->lineBreak();

        $lines = [];
        foreach ($this->options() as $key => $option) {
            $lines[] = "{$key}. {$option['label']}";
        }

        $this->menu->text(implode(PHP_EOL, $lines));
    }

    protected function afterRendering(string $argument): void
    {
        $options = $this->options();

        if (!array_key_exists($argument, $options)) {
            $this->fail($this->invalidMessage());

            return;
        }

        $option = $options[$argument];

        foreach ($option['set'] ?? [] as $key => $value) {
            $this->record->set($key, $value);
        }

        $this->decision->equal($argument, $option['next']);
    }

    protected function invalidMessage(): string
    {
        $lines = ["Invalid option.", '', $this->title(), ''];
        foreach ($this->options() as $key => $option) {
            $lines[] = "{$key}. {$option['label']}";
        }

        return implode(PHP_EOL, $lines);
    }
}
