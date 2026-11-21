<?php
declare(strict_types=1);

namespace IMS\Security;

use IMS\Core\Session;

/**
 * Sliding-Window In-Memory / Session Rate Limiter
 * Throttles brute force authentication attempts and high-frequency API abuse.
 */
class RateLimiter
{
    private const STORAGE_KEY = '_ims_rate_limits';

    public static function isAllowed(string $actionKey, int $maxAttempts = 5, int $decaySeconds = 60): bool
    {
        Session::start();
        $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        $key = hash('sha256', "{$actionKey}_{$ip}");
        $now = time();

        $limits = $_SESSION[self::STORAGE_KEY] ?? [];
        $record = $limits[$key] ?? ['attempts' => 0, 'first_attempt' => $now];

        // Reset if decay window expired
        if ($now - $record['first_attempt'] > $decaySeconds) {
            $record = ['attempts' => 1, 'first_attempt' => $now];
            $limits[$key] = $record;
            $_SESSION[self::STORAGE_KEY] = $limits;
            return true;
        }

        if ($record['attempts'] >= $maxAttempts) {
            return false;
        }

        $record['attempts']++;
        $limits[$key] = $record;
        $_SESSION[self::STORAGE_KEY] = $limits;
        return true;
    }

    public static function reset(string $actionKey): void
    {
        Session::start();
        $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        $key = hash('sha256', "{$actionKey}_{$ip}");

        if (isset($_SESSION[self::STORAGE_KEY][$key])) {
            unset($_SESSION[self::STORAGE_KEY][$key]);
        }
    }

    public static function getRemainingSeconds(string $actionKey, int $decaySeconds = 60): int
    {
        Session::start();
        $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        $key = hash('sha256', "{$actionKey}_{$ip}");
        $record = $_SESSION[self::STORAGE_KEY][$key] ?? null;

        if (!$record) {
            return 0;
        }

        $elapsed = time() - $record['first_attempt'];
        return max(0, $decaySeconds - $elapsed);
    }
}
