<?php

namespace App\Ussd\States\Shared;

use App\Ussd\Support\ErrorRetryTrait;
use App\Ussd\Support\Money;
use Sparors\Ussd\State;

/**
 * Generic itemised confirmation screen used by every payable journey
 * (business/market/barrier levies, property rates, licenses, permits).
 * Reads a "cart" the preceding states built up in the record:
 *
 *   cart_title    string               heading, e.g. "MARKET LEVY"
 *   cart_items    array<{label,amount}> line items to itemise
 *   cart_total    float                total amount due
 *   cart_category string               service_category for the transaction
 *   cart_meta     array                arbitrary breakdown data (JSON'able)
 *   cart_cancel_next string            state class to go to on 00 (default MainMenuState)
 */
class ConfirmPaymentState extends State
{
    use ErrorRetryTrait;

    protected function beforeRendering(): void
    {
        $error = $this->consumeError();

        if ($error) {
            $this->menu->text($error);

            return;
        }

        $title = $this->record->get('cart_title', 'PAYMENT');
        $items = $this->record->get('cart_items', []);
        $total = (float) $this->record->get('cart_total', 0);

        $this->menu->line($title)->lineBreak();

        foreach ($items as $item) {
            $this->menu->line("{$item['label']}: " . Money::fmt((float) $item['amount']));
        }

        $this->menu
            ->line('TOTAL: ' . Money::fmt($total))
            ->lineBreak()
            ->line('Reply with')
            ->line('1. Confirm payment')
            ->text('00. Cancel');
    }

    protected function afterRendering(string $argument): void
    {
        if ($argument === '1') {
            $requiresPin = $this->record->get('cart_requires_pin', true);

            $this->decision->any(
                $requiresPin ? PinEntryState::class : \App\Ussd\Actions\InitiatePaymentAction::class
            );

            return;
        }

        if ($argument === '00') {
            $cancelNext = $this->record->get('cart_cancel_next', \App\Ussd\States\MainMenuState::class);
            $this->record->deleteMultiple(['cart_title', 'cart_items', 'cart_total', 'cart_category', 'cart_meta', 'cart_cancel_next', 'cart_requires_pin']);
            $this->decision->any($cancelNext);

            return;
        }

        $this->fail("Invalid option.\n\nReply with\n1. Confirm payment\n00. Cancel");
    }
}
