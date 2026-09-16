<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Session-drop resume capability.
 *
 * The sparors/laravel-ussd package keys every piece of session state under
 * the gateway's SESSION_ID via its Record class (cache key pattern
 * "ussd_{sessionId}.{key}"). A dropped session almost always gets a brand
 * new SESSION_ID when the subscriber redials, so that cached state is
 * orphaned. To offer "resume where you left off", we take a full snapshot
 * of every cache row belonging to a session — keyed by phone number,
 * short-lived (config('ussdgateway.resume_ttl_seconds'), default 3 min) —
 * and, on request, replay those exact rows under the new SESSION_ID before
 * the ussd machine runs again.
 *
 * This relies on the `database` cache store so the raw rows can be read
 * and rewritten directly (config('ussd.cache_store') = 'database').
 */
class UssdResumeService
{
    protected function store()
    {
        return Cache::store(config('ussd.cache_store', 'database'));
    }

    protected function prefix(): string
    {
        $store = $this->store()->getStore();

        return method_exists($store, 'getPrefix') ? $store->getPrefix() : '';
    }

    protected function resumeKey(string $phone): string
    {
        return "ussd_resume.{$phone}";
    }

    /**
     * Copy every cache row for this USSD session into a phone-keyed
     * snapshot. Call after every non-terminal machine run.
     */
    public function snapshot(string $sessionId, string $phone): bool
    {
        $needle = "ussd_{$sessionId}.";
        $rows = DB::table('cache')->where('key', 'like', '%' . $needle . '%')->get();

        if ($rows->isEmpty()) {
            return false;
        }

        $data = [];
        foreach ($rows as $row) {
            $pos = strpos($row->key, $needle);
            if ($pos === false) {
                continue;
            }
            $suffix = substr($row->key, $pos + strlen($needle));
            $data[$suffix] = $row->value;
        }

        $ttl = (int) config('ussdgateway.resume_ttl_seconds', 180);
        $this->store()->put($this->resumeKey($phone), $data, $ttl);

        return true;
    }

    public function hasResume(string $phone): bool
    {
        return $this->store()->has($this->resumeKey($phone));
    }

    /**
     * Replay a previously snapshotted session's raw cache rows under a new
     * session id, effectively teleporting the machine back to where it was.
     */
    public function restore(string $sessionId, string $phone): bool
    {
        $data = $this->store()->get($this->resumeKey($phone));

        if (!$data) {
            return false;
        }

        $ttl = (int) config('ussd.cache_ttl') ?: (int) config('ussdgateway.session_ttl_seconds', 300);
        $prefix = $this->prefix();
        $expiration = now()->addSeconds($ttl)->getTimestamp();

        foreach ($data as $suffix => $rawValue) {
            DB::table('cache')->updateOrInsert(
                ['key' => $prefix . "ussd_{$sessionId}.{$suffix}"],
                ['value' => $rawValue, 'expiration' => $expiration]
            );
        }

        return true;
    }

    public function activeStateClass(string $phone): ?string
    {
        $data = $this->store()->get($this->resumeKey($phone));

        if (!$data || !isset($data['__active'])) {
            return null;
        }

        return $this->unserializeCacheValue($data['__active']);
    }

    public function clear(string $phone): void
    {
        $this->store()->forget($this->resumeKey($phone));
    }

    protected function unserializeCacheValue($value)
    {
        if (is_numeric($value)) {
            return $value;
        }

        $unserialized = @unserialize($value);

        return $unserialized === false && $value !== serialize(false) ? $value : $unserialized;
    }
}
