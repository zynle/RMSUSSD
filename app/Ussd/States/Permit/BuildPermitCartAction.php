<?php

namespace App\Ussd\States\Permit;

use App\Models\PermitRecord;
use App\Ussd\States\MainMenuState;
use App\Ussd\States\Shared\ConfirmPaymentState;
use Sparors\Ussd\Action;

class BuildPermitCartAction extends Action
{
    public function run(): string
    {
        $phone = $this->record->get('phoneNumber');
        $permitNo = $this->record->get('permit_no');

        $permit = PermitRecord::where('permit_no', $permitNo)
            ->where('phone', $phone)
            ->where('status', 'unpaid')
            ->first();

        if (!$permit) {
            $this->record->set(
                PermitNumberState::class . '_error',
                "Permit number not found or already paid.\n\nEnter Permit No.:"
            );

            return PermitNumberState::class;
        }

        $this->record->set('cart_title', strtoupper($permit->holder_name));
        $this->record->set('cart_items', [
            ['label' => "Service: {$permit->type}", 'amount' => (float) $permit->amount],
        ]);
        $this->record->set('cart_total', (float) $permit->amount);
        $this->record->set('cart_category', 'permit');
        $this->record->set('cart_meta', ['permit_no' => $permit->permit_no]);
        $this->record->set('cart_cancel_next', MainMenuState::class);

        $this->record->deleteMultiple(['permit_type', 'permit_no']);

        return ConfirmPaymentState::class;
    }
}
