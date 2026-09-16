<?php

namespace App\Jobs;

use App\Models\LicenseRecord;
use App\Models\PermitRecord;
use App\Models\PropertyRecord;
use App\Models\Transaction;
use App\Services\PaymentGatewayService;
use App\Services\SmsService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Polls ZynlePay for the outcome of a mobile money push that was already
 * sent to the customer's phone. The USSD session that started this
 * payment has already ended (see App\Ussd\Actions\InitiatePaymentAction)
 * — this job runs entirely out of band, on a queue worker, and is the
 * ONLY place a success/failure SMS is sent from. It re-throws while the
 * payment is still pending so Laravel's queue retry/backoff mechanism
 * does the polling for us; failed() fires once tries are exhausted with
 * no resolution, which we treat as a timeout.
 */
class CheckZynlePayPaymentJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries;
    public int $backoff;

    public function __construct(public int $transactionId)
    {
        $this->tries = (int) config('zynlepay.poll_max_attempts', 24);
        $this->backoff = (int) config('zynlepay.poll_interval_seconds', 5);
    }

    public function handle(PaymentGatewayService $gateway, SmsService $sms): void
    {
        $transaction = Transaction::find($this->transactionId);

        // Already resolved (e.g. a previous attempt of this same job got
        // the final answer right before a retry fired) or gone — nothing
        // to do.
        if (!$transaction || $transaction->status !== 'pending') {
            return;
        }

        $result = $gateway->pollStatus($transaction->reference);

        if ($result['status'] === 'pending') {
            Log::debug("Payment {$transaction->reference} still pending (attempt {$this->attempts()}/{$this->tries})");

            // Throwing triggers Laravel's normal retry/backoff — this is
            // the polling loop, just expressed as queue retries instead
            // of a blocking sleep().
            throw new \RuntimeException("Payment {$transaction->reference} still pending customer approval.");
        }

        $this->resolve($transaction, $result['status'], $result['gateway_reference'], $result['response']);
    }

    /**
     * Tries exhausted with the gateway still saying "pending" every time —
     * the customer never approved (or rejected) the prompt in time.
     */
    public function failed(\Throwable $e): void
    {
        $transaction = Transaction::find($this->transactionId);

        if (!$transaction || $transaction->status !== 'pending') {
            return;
        }

        Log::warning("Payment {$transaction->reference} timed out waiting for customer approval after {$this->tries} attempts.");

        $this->resolve($transaction, 'failed', null, ['timeout' => true]);
    }

    protected function resolve(Transaction $transaction, string $status, ?string $gatewayReference, mixed $rawResponse): void
    {
        $transaction->update([
            'status' => $status,
            'gateway_reference' => $gatewayReference,
            'gateway_response' => json_encode($rawResponse),
            'completed_at' => now(),
        ]);

        $title = $transaction->breakdown['title'] ?? 'your payment';
        $phone = $transaction->phone;
        $amount = number_format((float) $transaction->amount, 2);

        $sms = app(SmsService::class);

        if ($status === 'success') {
            $this->markSourceRecordsPaid($transaction->service_category, $transaction->breakdown['meta'] ?? []);

            $sms->send(
                $phone,
                "Payment of ZMW {$amount} for {$title} was successful. Ref: {$transaction->reference}. Thank you."
            );

            return;
        }

        $sms->send(
            $phone,
            "Your payment of ZMW {$amount} for {$title} was not completed. Ref: {$transaction->reference}. Please dial the USSD code to try again."
        );
    }

    protected function markSourceRecordsPaid(string $category, array $meta): void
    {
        if ($category === 'property_rates' && !empty($meta['plot_nos'])) {
            PropertyRecord::whereIn('plot_no', $meta['plot_nos'])->update(['status' => 'paid']);
        }

        if ($category === 'license' && !empty($meta['license_no'])) {
            LicenseRecord::where('license_no', $meta['license_no'])->update(['status' => 'paid']);
        }

        if ($category === 'permit' && !empty($meta['permit_no'])) {
            PermitRecord::where('permit_no', $meta['permit_no'])->update(['status' => 'paid']);
        }
    }
}
