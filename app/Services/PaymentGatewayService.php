<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

/**
 * Thin wrapper around the ZynlePay mobile-money collection API, adapted
 * from ModziPayMiddleware's ZynlePayHelper. A mock mode is provided so the
 * full USSD payment journey can be exercised without live credentials —
 * flip ZYNLEPAY_MOCK=false in .env and fill in the zynlepay.* keys to go
 * live (see config/zynlepay.php).
 */
class PaymentGatewayService
{
    /**
     * Initiate a mobile money collection (debit the subscriber) and poll
     * for the outcome, mirroring ZynlePayHelper::processCollection.
     *
     * @return array{status: string, gateway_reference: ?string, response: mixed}
     */
    public function collect(string $phone, float $amount, string $reference): array
    {
        $phone = str_replace('+', '', $phone);

        if (config('zynlepay.mock')) {
            return $this->mockCollect($phone, $amount, $reference);
        }

        $initiate = $this->momoDebit($phone, $amount, $reference);

        if (empty($initiate) || ($initiate->response_code ?? null) != '120') {
            Log::warning('ZynlePay collection could not be initiated', [
                'phone' => $phone, 'reference' => $reference, 'response' => $initiate,
            ]);

            return ['status' => 'failed', 'gateway_reference' => null, 'response' => $initiate];
        }

        $maxRetries = (int) config('zynlepay.max_status_retries', 6);
        $interval = (int) config('zynlepay.status_retry_seconds', 2);

        for ($i = 0; $i < $maxRetries; $i++) {
            $status = $this->checkStatus($reference);
            $code = (string) ($status->response_code ?? '');

            if ($code === '100') {
                return ['status' => 'success', 'gateway_reference' => $status->reference_no ?? $reference, 'response' => $status];
            }

            if ($code === '995') {
                return ['status' => 'failed', 'gateway_reference' => $reference, 'response' => $status];
            }

            sleep($interval);
        }

        Log::warning('ZynlePay collection timed out waiting for status', ['reference' => $reference]);

        return ['status' => 'failed', 'gateway_reference' => $reference, 'response' => null];
    }

    protected function mockCollect(string $phone, float $amount, string $reference): array
    {
        $outcome = config('zynlepay.mock_outcome', 'success');

        $status = match ($outcome) {
            'failed' => 'failed',
            'random' => (mt_rand(1, 100) <= 85) ? 'success' : 'failed',
            default => 'success',
        };

        Log::info('ZynlePay [MOCK] collection simulated', [
            'phone' => $phone, 'amount' => $amount, 'reference' => $reference, 'status' => $status,
        ]);

        return [
            'status' => $status,
            'gateway_reference' => 'MOCK-' . $reference,
            'response' => ['mock' => true, 'outcome' => $status],
        ];
    }

    protected function momoDebit(string $phone, float $amount, string $reference)
    {
        $payload = [
            'auth' => [
                'merchant_id' => config('zynlepay.merchant_id'),
                'api_id' => config('zynlepay.api_id'),
                'api_key' => config('zynlepay.api_key'),
                'service_id' => config('zynlepay.service_id'),
                'channel' => config('zynlepay.channel'),
            ],
            'data' => [
                'method' => 'runBillPayment',
                'request_id' => $reference,
                'sender_id' => $phone,
                'reference_no' => $reference,
                'amount' => (string) $amount,
            ],
        ];

        return $this->post(config('zynlepay.base_url'), $payload);
    }

    protected function checkStatus(string $reference)
    {
        $payload = [
            'api_id' => config('zynlepay.status_api_id'),
            'api_key' => config('zynlepay.status_api_key'),
            'reference_no' => $reference,
        ];

        return $this->post(config('zynlepay.status_url'), $payload);
    }

    protected function post(string $url, array $payload)
    {
        $curl = curl_init();

        curl_setopt_array($curl, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 15,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
        ]);

        $response = curl_exec($curl);
        $error = curl_error($curl);
        curl_close($curl);

        if ($response === false) {
            Log::error('ZynlePay HTTP call failed', ['url' => $url, 'error' => $error]);

            return null;
        }

        return json_decode($response);
    }
}
