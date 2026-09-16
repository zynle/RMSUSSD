<?php

namespace App\Ussd\Support;

use Sparors\Ussd\State;

/**
 * Generic free-text prompt (names, business names, license/permit numbers)
 * with a pluggable validator and a fixed next state.
 */
abstract class TextInputState extends State
{
    use ErrorRetryTrait;

    abstract protected function prompt(): string;

    abstract protected function recordKey(): string;

    abstract protected function nextState(): string;

    /** Return null if valid, or an error message string if invalid. */
    protected function validate(string $value): ?string
    {
        return trim($value) === '' ? 'This field cannot be empty.' : null;
    }

    protected function normalize(string $value): string
    {
        return trim($value);
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
        $error = $this->validate($argument);

        if ($error) {
            $this->fail("{$error}\n\n" . $this->prompt());

            return;
        }

        $this->record->set($this->recordKey(), $this->normalize($argument));
        $this->decision->any($this->nextState());
    }
}
