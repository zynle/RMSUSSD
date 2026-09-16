<?php

namespace App\Ussd\States\Registration;

use App\Ussd\Support\ErrorRetryTrait;
use Sparors\Ussd\State;

class PinConfirmState extends State
{
    use ErrorRetryTrait;

    protected function beforeRendering(): void
    {
        $error = $this->consumeError();

        if ($error) {
            $this->menu->text($error);

            return;
        }

        $this->menu->text('Re-enter your 4-digit PIN to confirm:');
    }

    protected function afterRendering(string $argument): void
    {
        $pin = $this->record->get('reg_pin');

        if (trim($argument) !== $pin) {
            $this->record->delete('reg_pin');
            $this->record->set(
                PinCreateState::class . '_error',
                "PINs did not match. Please try again.\n\nCreate a 4-digit PIN to secure your payments:"
            );
            $this->decision->any(PinCreateState::class);

            return;
        }

        $this->decision->any(RegistrationConfirmState::class);
    }
}
