<?php

namespace App\Jobs;

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
 * Actually calls the payment gateway to send the mobile money push. This
 * runs entirely on the queue — never inline in the USSD request/response
 * cycle — so a slow or unresponsive gateway can never tie up the process
 * handling live USSD traffic (see App\Ussd\Actions\InitiatePaymentAction,
 * and docs/README §2a).
 *
 * A couple of quick retries here are for transient network blips talking
 * to the gateway itself, not for the customer's approval — that's an
 * entirely separate, much longer wait handled by CheckZynlePayPaymentJob.
 */
class SendPaymentPushJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 3;

    public function __construct(public int $transactionId)
    {
    }

    public function handle(PaymentGatewayService $gateway): void
    {
        $transaction = Transaction::find($this->transactionId);

        if (!$transaction || $transaction->status !== 'pending') {
            return;
        }

        $result = $gateway->initiate($transaction->phone, (float) $transaction->amount, $transaction->reference);

        if (!$result['accepted']) {
            // Let the normal retry/backoff above have a few goes in case
            // this was a transient gateway/network error, not a hard
            // rejection — resolve() below (via failed()) only fires once
            // those are exhausted.
            throw new \RuntimeException("ZynlePay declined to accept push for {$transaction->reference}.");
        }

        CheckZynlePayPaymentJob::dispatch($transaction->id)
            ->delay(now()->addSeconds(CheckZynlePayPaymentJob::firstDelaySeconds()));
    }

    /**
     * Every retry of the push itself failed — the gateway never accepted
     * the request at all. No prompt was ever sent to the customer, so
     * this is communicated by SMS just like every other outcome (the
     * USSD session already ended the instant this job was enqueued).
     */
    public function failed(\Throwable $e): void
    {
        $transaction = Transaction::find($this->transactionId);

        if (!$transaction || $transaction->status !== 'pending') {
            return;
        }

        Log::warning("Could not send payment push for {$transaction->reference}: {$e->getMessage()}");

        $transaction->update([
            'status' => 'failed',
            'gateway_response' => json_encode(['error' => 'push_not_accepted', 'message' => $e->getMessage()]),
            'completed_at' => now(),
        ]);

        $title = $transaction->breakdown['title'] ?? 'your payment';

        app(SmsService::class)->send(
            $transaction->phone,
            "We could not start payment of ZMW " . number_format((float) $transaction->amount, 2) . " for {$title}. Ref: {$transaction->reference}. Please dial the USSD code to try again."
        );
    }
}
