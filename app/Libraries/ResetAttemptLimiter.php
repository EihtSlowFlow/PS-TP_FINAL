<?php

namespace App\Libraries;

/**
 * Cooldown for password reset requests that do not match an active account (HU-03b).
 *
 * After MAX_ATTEMPTS non-matching attempts on the same key, that key is locked for COOLDOWN_SECONDS.
 * Only non-matching attempts are counted, and the lock applies whether or not the email exists, so it
 * reveals nothing about which accounts exist. The state lives in the framework cache.
 */
class ResetAttemptLimiter
{
    public const MAX_ATTEMPTS     = 3;
    public const COOLDOWN_SECONDS = 120;

    private const KEY_PREFIX = 'pwreset_';

    /**
     * Seconds left on the cooldown of any of the given identifiers (0 when none is locked).
     *
     * @param list<string> $identifiers e.g. 'ip:203.0.113.5', 'email:someone@example.com'
     */
    public function secondsLocked(array $identifiers): int
    {
        $remaining = 0;

        foreach ($identifiers as $identifier) {
            $lockedUntil = cache()->get($this->lockKey($identifier));

            if (is_int($lockedUntil)) {
                $remaining = max($remaining, $lockedUntil - time());
            }
        }

        return max(0, $remaining);
    }

    /**
     * Counts one non-matching attempt per identifier; the third one starts the cooldown.
     *
     * @param list<string> $identifiers
     */
    public function registerFailure(array $identifiers): void
    {
        foreach ($identifiers as $identifier) {
            $count = (int) cache()->get($this->attemptsKey($identifier)) + 1;

            if ($count >= self::MAX_ATTEMPTS) {
                cache()->save($this->lockKey($identifier), time() + self::COOLDOWN_SECONDS, self::COOLDOWN_SECONDS);
                cache()->delete($this->attemptsKey($identifier));

                continue;
            }

            // The counter outlives the cooldown so slow guessing is still counted.
            cache()->save($this->attemptsKey($identifier), $count, self::COOLDOWN_SECONDS * 5);
        }
    }

    /**
     * Forgets the counter and the cooldown of the given identifiers.
     *
     * @param list<string> $identifiers
     */
    public function clear(array $identifiers): void
    {
        foreach ($identifiers as $identifier) {
            cache()->delete($this->attemptsKey($identifier));
            cache()->delete($this->lockKey($identifier));
        }
    }

    private function attemptsKey(string $identifier): string
    {
        return self::KEY_PREFIX . 'attempts_' . hash('sha256', $identifier);
    }

    private function lockKey(string $identifier): string
    {
        return self::KEY_PREFIX . 'lock_' . hash('sha256', $identifier);
    }
}

