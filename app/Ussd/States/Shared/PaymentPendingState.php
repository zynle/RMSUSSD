<?php

namespace App\Ussd\States\Shared;

use App\Ussd\Support\Money;
use Sparors\Ussd\State;

/**
 * Terminal screen shown the instant the mobile money push has been sent
 * — before we know whether the customer will approve it. The actual
 * outcome is confirmed later by SMS (see App\Jobs\CheckZynlePayPaymentJob).
 *
 * Kept deliberately short: USSD aggregators cap a single screen at
 * roughly 160-182 characters (GSM 03.38 packing) and will silently
 * paginate anything longer, appending their own "next" prompt and
 * forcing the phone to show an input box — which defeats the point of a
 * self-closing terminal screen regardless of REQUEST_TYPE. Also
 * explicitly says "mobile money PIN" so it isn't mistaken for the
 * Council PIN just entered on the previous screen (see PinEntryState).
 */
class PaymentPendingState extends State
{
    protected $action = self::PROMPT;

    protected function beforeRendering(): void
    {
        $amount = (float) $this->record->get('pending_amount', 0);
        $reference = $this->record->get('pending_reference', '-');

        $this->record->deleteMultiple(['pending_title', 'pending_amount', 'pending_reference']);

        $this->menu
            ->line('Payment prompt for ' . Money::fmt($amount) . ' sent to your phone.')
            ->line('Enter your MOBILE MONEY PIN there to approve.')
            ->text("Ref: {$reference}");
    }

    protected function afterRendering(string $argument): void
    {
        $this->decision->any(self::class);
    }
}
