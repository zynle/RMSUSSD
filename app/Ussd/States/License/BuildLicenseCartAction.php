<?php

namespace App\Ussd\States\License;

use App\Models\LicenseRecord;
use App\Ussd\States\MainMenuState;
use App\Ussd\States\Shared\ConfirmPaymentState;
use Sparors\Ussd\Action;

class BuildLicenseCartAction extends Action
{
    public function run(): string
    {
        $phone = $this->record->get('phoneNumber');
        $licenseNo = $this->record->get('license_no');

        $license = LicenseRecord::where('license_no', $licenseNo)
            ->where('phone', $phone)
            ->where('status', 'unpaid')
            ->first();

        if (!$license) {
            $this->record->set(
                LicenseNumberState::class . '_error',
                "License number not found or already paid.\n\nEnter License No.:"
            );

            return LicenseNumberState::class;
        }

        $this->record->set('cart_title', strtoupper($license->holder_name));
        $this->record->set('cart_items', [
            ['label' => "Service: {$license->type}", 'amount' => (float) $license->amount],
        ]);
        $this->record->set('cart_total', (float) $license->amount);
        $this->record->set('cart_category', 'license');
        $this->record->set('cart_meta', ['license_no' => $license->license_no]);
        $this->record->set('cart_cancel_next', MainMenuState::class);

        $this->record->deleteMultiple(['license_type', 'license_no']);

        return ConfirmPaymentState::class;
    }
}
