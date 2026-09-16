<?php

namespace App\Ussd\States\Registration;

use App\Ussd\Support\ErrorRetryTrait;
use Sparors\Ussd\State;

class RegistrationConfirmState extends State
{
    use ErrorRetryTrait;

    protected function beforeRendering(): void
    {
        $error = $this->consumeError();

        if ($error) {
            $this->menu->text($error);

            return;
        }

        $first = $this->record->get('reg_first_name');
        $last = $this->record->get('reg_last_name');
        $gender = ucfirst((string) $this->record->get('reg_gender'));
        $market = $this->record->get('reg_market_name');
        $shop = $this->record->get('reg_shop_no');
        $location = $this->record->get('reg_location');

        $this->menu
            ->line("Name: {$first} {$last}")
            ->line("Gender: {$gender}")
            ->line("Location: {$location}");

        if ($market) {
            $this->menu->line("Market: {$market}");
        }
        if ($shop) {
            $this->menu->line("Shop No: {$shop}");
        }

        $this->menu
            ->lineBreak()
            ->line('1. Confirm Registration')
            ->text('00. Cancel');
    }

    protected function afterRendering(string $argument): void
    {
        if ($argument === '1') {
            $this->decision->any(\App\Ussd\Actions\RegisterCustomerAction::class);

            return;
        }

        if ($argument === '00') {
            $this->record->set('goodbye_message', 'Registration cancelled. Dial the USSD code again to retry.');
            $this->decision->any(\App\Ussd\States\Errors\GoodbyeState::class);

            return;
        }

        $this->fail("Invalid option.\n\n1. Confirm Registration\n00. Cancel");
    }
}
