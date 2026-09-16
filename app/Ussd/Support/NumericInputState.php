<?php

namespace App\Ussd\Support;

use Sparors\Ussd\State;

/**
 * Generic "enter a whole number" prompt (number of employees, tables,
 * animals, logs, drums, etc.) with min/max bounds and a fixed next state.
 */
abstract class NumericInputState extends State
{
    use ErrorRetryTrait;

    abstract protected function prompt(): string;

    abstract protected function recordKey(): string;

    abstract protected function nextState(): string;

    protected function min(): int
    {
        return 1;
    }

    protected function max(): int
    {
        return 999;
    }

    protected function beforeRendering(): void
    {
        $error = $this->consumeError();

        if ($error) {
            $this->menu->text($error);

            return;
        }

        $this->menu->text($this->prompt());
    }

    protected function afterRendering(string $argument): void
    {
        $value = trim($argument);

        if (!ctype_digit($value) || (int) $value < $this->min() || (int) $value > $this->max()) {
            $this->fail("Invalid number. Please enter a whole number between {$this->min()} and {$this->max()}.\n\n" . $this->prompt());

            return;
        }

        $this->record->set($this->recordKey(), (int) $value);
        $this->decision->any($this->nextState());
    }
}
