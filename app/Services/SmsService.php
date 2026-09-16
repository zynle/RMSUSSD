<?php

namespace App\Services;

use App\Models\SmsLog;
use Illuminate\Support\Facades\Log;

/**
 * Wrapper around the Zynle SMS API, adapted from ModziPayMiddleware's
 * ZynleSMS helper. Runs in mock mode by default (SMS_MOCK=true) so every
 * notification point in the USSD journey can be verified without a live
 * SMS gateway — every message, sent or mocked, is recorded in sms_logs.
 */
class SmsService
{
    public function send(string $phone, string $message): SmsLog
    {
        if (config('smsgateway.mock')) {
            Log::info("SMS [MOCK] to {$phone}: {$message}");

            return SmsLog::create([
                'phone' => $phone,
                'message' => $message,
                'status' => 'mocked',
                'provider_response' => null,
            ]);
        }

        $response = $this->post($phone, $message);

        return SmsLog::create([
            'phone' => $phone,
            'message' => $message,
            'status' => $response !== null ? 'sent' : 'failed',
            'provider_response' => $response,
        ]);
    }

    protected function post(string $phone, string $message): ?string
    {
        $payload = [
            'senderId' => config('smsgateway.sender_id'),
            'message' => $message,
            'mobileNumbers' => $phone,
            'apiKey' => config('smsgateway.api_key'),
            'clientId' => config('smsgateway.client_id'),
        ];

        $curl = curl_init();

        curl_setopt_array($curl, [
            CURLOPT_URL => config('smsgateway.base_url'),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 15,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_SSL_VERIFYPEER => 0,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
        ]);

        $response = curl_exec($curl);
        $error = curl_error($curl);
        curl_close($curl);

        if ($response === false) {
            Log::error('SMS gateway call failed', ['error' => $error]);

            return null;
        }

        Log::info('SMS gateway response', ['response' => $response]);

        return $response;
    }
}
