<?php

namespace App\Ussd\Support;

/**
 * Shared "show error, then re-render the same prompt" pattern used by
 * (almost) every state in this app: each state stores its own transient
 * error string under a `{state}_error` record key, consumed exactly once
 * in beforeRendering(). Keeps every state's control flow identical to
 * the altusMiddleware reference implementation this app is modelled on.
 */
trait ErrorRetryTrait
{
    protected function errorKey(): string
    {
        return static::class . '_error';
    }

    protected function consumeError(): ?string
    {
        $error = $this->record->get($this->errorKey());

        if ($error) {
            $this->record->delete($this->errorKey());
        }

        return $error;
    }

    protected function fail(string $message): void
    {
        $this->record->set($this->errorKey(), $message);
        $this->decision->any(static::class);
    }
}
