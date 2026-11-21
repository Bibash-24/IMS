<?php
declare(strict_types=1);

namespace IMS\Security;

use IMS\Core\Session;

/**
 * Enterprise Anti-CSRF Protection Middleware
 * Generates cryptographically secure timing-safe Anti-CSRF tokens.
 */
class CsrfGuard
{
    private const SESSION_TOKEN_KEY = '_ims_csrf_token';
    private const SESSION_EXPIRY_KEY = '_ims_csrf_expiry';
    private const DEFAULT_TTL = 3600; // 1 hour

    public static function generateToken(): string
    {
        Session::start();
        $token = bin2hex(random_bytes(32));
        $_SESSION[self::SESSION_TOKEN_KEY] = $token;
        $_SESSION[self::SESSION_EXPIRY_KEY] = time() + self::DEFAULT_TTL;
        return $token;
    }

    public static function getToken(): string
    {
        Session::start();
        $token = $_SESSION[self::SESSION_TOKEN_KEY] ?? null;
        $expiry = $_SESSION[self::SESSION_EXPIRY_KEY] ?? 0;

        if (!$token || time() > $expiry) {
            return self::generateToken();
        }

        return $token;
    }

    public static function isValidToken(?string $token): bool
    {
        if ($token === null || $token === '') {
            return false;
        }

        Session::start();
        $sessionToken = $_SESSION[self::SESSION_TOKEN_KEY] ?? null;
        $expiry = $_SESSION[self::SESSION_EXPIRY_KEY] ?? 0;

        if (!$sessionToken || time() > $expiry) {
            return false;
        }

        return hash_equals($sessionToken, $token);
    }

    public static function requireValidToken(?string $token = null): void
    {
        $submittedToken = $token ?? ($_POST['csrf_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? null));

        if (!self::isValidToken($submittedToken)) {
            http_response_code(403);
            die(json_encode([
                'success' => false,
                'error' => 'CSRF Token Validation Failed. Request Terminated.',
                'code' => 403
            ]));
        }
    }

    public static function getFormField(): string
    {
        $token = self::getToken();
        return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token, ENT_QUOTES, 'UTF-8') . '">';
    }
}
