<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Thin wrapper around the ZynlePay mobile-money collection API, adapted
 * from ModziPayMiddleware's ZynlePayHelper. A mock mode is provided so the
 * full USSD payment journey can be exercised without live credentials —
 * flip ZYNLEPAY_MOCK=false in .env and fill in the zynlepay.* keys to go
 * live (see config/zynlepay.php).
 *
 * IMPORTANT — this is a two-step, asynchronous API by design:
 *   1. initiate() only sends the mobile money push/prompt and reports
 *      whether the gateway *accepted the request* (not whether the
 *      customer approved it — that can take anywhere from a few seconds
 *      to a couple of minutes, and USSD sessions cannot stay open that
 *      long). The USSD session must end here.
 *   2. pollStatus() is called later, out of band (see
 *      App\Jobs\CheckZynlePayPaymentJob), to find out what the customer
 *      actually did. Never block an HTTP/USSD request on this.
 */
class PaymentGatewayService
{
    /**
     * Send the mobile money collection request (the push/prompt to the
     * subscriber's phone). Returns immediately — this does NOT wait for
     * the customer to approve or reject it.
     *
     * @return array{accepted: bool, response: mixed}
     */
    public function initiate(string $phone, float $amount, string $reference): array
    {
        $phone = str_replace('+', '', $phone);

        if (config('zynlepay.mock')) {
            $rejected = config('zynlepay.mock_reject_push', false);

            Log::info('ZynlePay [MOCK] push ' . ($rejected ? 'rejected' : 'initiated'), ['phone' => $phone, 'amount' => $amount, 'reference' => $reference]);

            return ['accepted' => !$rejected, 'response' => ['mock' => true, 'rejected' => $rejected]];
        }

        $response = $this->momoDebit($phone, $amount, $reference);
        $accepted = !empty($response) && ($response->response_code ?? null) == '120';

        if (!$accepted) {
            Log::warning('ZynlePay collection could not be initiated', [
                'phone' => $phone, 'reference' => $reference, 'response' => $response,
            ]);
        }

        return ['accepted' => $accepted, 'response' => $response];
    }

    /**
     * Ask the gateway for the current status of a previously-initiated
     * collection. Meant to be called repeatedly (with backoff) from a
     * queued job until it resolves — never in a request/response cycle.
     *
     * @return array{status: 'success'|'failed'|'pending', gateway_reference: ?string, response: mixed}
     */
    public function pollStatus(string $reference): array
    {
        if (config('zynlepay.mock')) {
            // Optionally simulate a customer who takes a few polls to
            // approve/reject the prompt, so the retry/backoff loop in
            // CheckZynlePayPaymentJob can be exercised realistically
            // instead of always resolving on the first poll.
            $pendingTicks = (int) config('zynlepay.mock_pending_ticks', 0);

            if ($pendingTicks > 0) {
                $tickKey = "zynlepay_mock_ticks.{$reference}";
                $seen = (int) Cache::get($tickKey, 0);

                if ($seen < $pendingTicks) {
                    Cache::put($tickKey, $seen + 1, now()->addMinutes(10));
                    Log::info('ZynlePay [MOCK] status poll — still pending', ['reference' => $reference, 'tick' => $seen + 1, 'of' => $pendingTicks]);

                    return ['status' => 'pending', 'gateway_reference' => null, 'response' => ['mock' => true, 'outcome' => 'pending']];
                }

                Cache::forget($tickKey);
            }

            $outcome = config('zynlepay.mock_outcome', 'success');
            $status = match ($outcome) {
                'failed' => 'failed',
                'random' => (mt_rand(1, 100) <= 85) ? 'success' : 'failed',
                default => 'success',
            };

            Log::info('ZynlePay [MOCK] status poll', ['reference' => $reference, 'status' => $status]);

            return ['status' => $status, 'gateway_reference' => 'MOCK-' . $reference, 'response' => ['mock' => true, 'outcome' => $status]];
        }

        $response = $this->checkStatus($reference);
        $code = (string) ($response->response_code ?? '');

        $status = match ($code) {
            '100' => 'success',
            '995' => 'failed',
            default => 'pending',
        };

        return ['status' => $status, 'gateway_reference' => $response->reference_no ?? $reference, 'response' => $response];
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
