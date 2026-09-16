<?php

namespace App\Http\Controllers;

use App\Services\UssdResumeService;
use App\Ussd\Actions\StartAction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Sparors\Ussd\Facades\Ussd;

class UssdController extends Controller
{
    public function handle(Request $request, UssdResumeService $resume): JsonResponse
    {
        $auth = $request->input('auth', []);

        if (($auth['api_id'] ?? '') !== config('ussdgateway.api_id') || ($auth['api_key'] ?? '') !== config('ussdgateway.api_key')) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $ussdRequest = $request->input('ussd_request', []);

        $sessionId = (string) ($ussdRequest['SESSION_ID'] ?? '');
        $phone = str_replace('+', '', (string) ($ussdRequest['MSISDN'] ?? ''));
        $message = (string) ($ussdRequest['MESSAGE'] ?? '');
        $operator = (string) ($ussdRequest['OPERATOR'] ?? '');

        if (!$sessionId || !$phone) {
            return response()->json([
                'ussd_response' => ['USSD_BODY' => 'Session error. Please try again.', 'REQUEST_TYPE' => '3'],
            ]);
        }

        $shortcodes = config('ussdgateway.shortcodes', []);
        usort($shortcodes, fn ($a, $b) => strlen($b) <=> strlen($a));

        $matchedShortcode = null;
        foreach ($shortcodes as $shortcode) {
            if ($message === $shortcode || str_starts_with($message, $shortcode . '*')) {
                $matchedShortcode = $shortcode;
                break;
            }
        }

        if ($matchedShortcode !== null && $message === $matchedShortcode) {
            $input = null;
        } else {
            $rest = $matchedShortcode !== null
                ? substr($message, strlen($matchedShortcode) + 1)
                : $message;
            $parts = explode('*', $rest);
            $input = end($parts);
        }

        Log::debug("USSD | session={$sessionId} phone={$phone} input=" . ($input ?? '(initial)'));

        $gateKey = "ussd_gate.{$sessionId}";

        // First request of a brand new dial: offer to resume a recently
        // dropped session for this phone number, if one exists.
        if ($input === null && $resume->hasResume($phone)) {
            Cache::store(config('ussd.cache_store', 'database'))->put($gateKey, true, 200);

            return response()->json([
                'ussd_response' => [
                    'USSD_BODY' => "You have a session in progress.\n\n1. Resume\n2. Main Menu",
                    'REQUEST_TYPE' => '2',
                ],
            ]);
        }

        $store = Cache::store(config('ussd.cache_store', 'database'));

        if ($store->has($gateKey)) {
            $store->forget($gateKey);

            if ($input === '1') {
                return $this->handleResumeChoice($sessionId, $phone, $resume);
            }

            // Any other input ("2", garbage, etc.) => discard the dropped
            // session and start completely fresh at the main menu / KYC.
            $resume->clear($phone);
            $input = null;
        }

        return $this->runMachine($sessionId, $phone, $operator, $input, $resume);
    }

    protected function handleResumeChoice(string $sessionId, string $phone, UssdResumeService $resume): JsonResponse
    {
        if (!$resume->restore($sessionId, $phone)) {
            // Snapshot expired between the gate prompt and the choice.
            return $this->runMachine($sessionId, $phone, '', null, $resume);
        }

        $activeClass = $resume->activeStateClass($phone);

        if (!$activeClass || !class_exists($activeClass)) {
            $resume->clear($phone);

            return $this->runMachine($sessionId, $phone, '', null, $resume);
        }

        try {
            $record = new \Sparors\Ussd\Record(
                Cache::store(config('ussd.cache_store', 'database')),
                $sessionId
            );

            $state = new $activeClass();
            $state->setRecord($record);
            $body = $state->render();
            $action = $state->getAction();

            $resume->snapshot($sessionId, $phone);

            return response()->json([
                'ussd_response' => [
                    'USSD_BODY' => $body,
                    'REQUEST_TYPE' => $action === 'prompt' ? '3' : '2',
                ],
            ]);
        } catch (\Throwable $e) {
            Log::error("USSD resume error | session={$sessionId}: " . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            $resume->clear($phone);

            return $this->runMachine($sessionId, $phone, '', null, $resume);
        }
    }

    protected function runMachine(string $sessionId, string $phone, string $operator, ?string $input, UssdResumeService $resume): JsonResponse
    {
        $responseData = [
            'ussd_response' => ['USSD_BODY' => 'An error occurred. Please try again.', 'REQUEST_TYPE' => '3'],
        ];
        $terminal = true;

        try {
            Ussd::machine()
                ->setSessionId($sessionId)
                ->setPhoneNumber($phone)
                ->setNetwork($operator)
                ->setInput($input)
                ->setInitialState(StartAction::class)
                ->setResponse(function (string $body, string $action) use (&$responseData, &$terminal) {
                    $terminal = $action === 'prompt';
                    $responseData = [
                        'ussd_response' => [
                            'USSD_BODY' => $body,
                            'REQUEST_TYPE' => $terminal ? '3' : '2',
                        ],
                    ];
                })
                ->run();
        } catch (\Throwable $e) {
            Log::error("USSD error | session={$sessionId}: " . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            $resume->clear($phone);

            return response()->json($responseData);
        }

        if ($terminal) {
            $resume->clear($phone);
        } else {
            $resume->snapshot($sessionId, $phone);
        }

        Log::debug("USSD response | session={$sessionId}: " . json_encode($responseData));

        return response()->json($responseData);
    }
}
