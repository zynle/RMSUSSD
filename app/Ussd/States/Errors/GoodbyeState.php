<?php

namespace App\Ussd\States\Errors;

use Sparors\Ussd\State;

class GoodbyeState extends State
{
    protected $action = self::PROMPT;

    protected function beforeRendering(): void
    {
        $message = $this->record->get('goodbye_message', 'Thank you for using the Local Government Digital Platform. Goodbye.');
        $this->record->delete('goodbye_message');

        $this->menu->text($message);
    }

    protected function afterRendering(string $argument): void
    {
        $this->decision->any(self::class);
    }
}
