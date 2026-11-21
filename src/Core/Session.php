<?php
declare(strict_types=1);

namespace IMS\Core;

/**
 * Enterprise RBAC Session Security Manager
 * Distinguishes guest, staff, and admin tiers with cryptographically randomized session IDs and inactivity timeouts.
 */
class Session
{
    public const ROLE_GUEST = 'guest';
    public const ROLE_STAFF = 'staff';
    public const ROLE_ADMIN = 'admin';

    public static function start(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            if (!headers_sent()) {
                @ini_set('session.cookie_httponly', '1');
                @ini_set('session.use_only_cookies', '1');
                if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
                    @ini_set('session.cookie_secure', '1');
                }
            }
            @session_start();
        }

        // Automatic session timeout after 2 hours
        $lastActivity = $_SESSION['_last_activity'] ?? time();
        if (time() - $lastActivity > Config::get('security.session_lifetime', 7200)) {
            self::destroy();
            @session_start();
        }
        $_SESSION['_last_activity'] = time();
    }

    public static function set(string $key, mixed $value): void
    {
        self::start();
        $_SESSION[$key] = $value;
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        self::start();
        return $_SESSION[$key] ?? $default;
    }

    public static function has(string $key): bool
    {
        self::start();
        return isset($_SESSION[$key]);
    }

    public static function remove(string $key): void
    {
        self::start();
        unset($_SESSION[$key]);
    }

    public static function destroy(): void
    {
        self::start();
        $_SESSION = [];
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        session_destroy();
    }

    public static function loginUser(array $userData, string $role = self::ROLE_STAFF): void
    {
        self::start();
        session_regenerate_id(true);
        $_SESSION['user_id'] = $userData['id'] ?? null;
        $_SESSION['user_name'] = $userData['name'] ?? 'Staff Member';
        $_SESSION['user_email'] = $userData['email'] ?? '';
        $_SESSION['user_role'] = $role;
        $_SESSION['login_time'] = time();
    }

    public static function loginAdmin(array $adminData): void
    {
        self::loginUser($adminData, self::ROLE_ADMIN);
    }

    public static function getRole(): string
    {
        self::start();
        return $_SESSION['user_role'] ?? self::ROLE_GUEST;
    }

    public static function isLoggedIn(): bool
    {
        self::start();
        return isset($_SESSION['user_id']) && self::getRole() !== self::ROLE_GUEST;
    }

    public static function isAdmin(): bool
    {
        return self::isLoggedIn() && self::getRole() === self::ROLE_ADMIN;
    }

    public static function isStaff(): bool
    {
        return self::isLoggedIn() && (self::getRole() === self::ROLE_STAFF || self::getRole() === self::ROLE_ADMIN);
    }

    public static function requireLogin(string $redirect = 'index.html'): void
    {
        if (!self::isLoggedIn()) {
            header("Location: {$redirect}?error=auth_required");
            exit();
        }
    }

    public static function requireAdmin(string $redirect = 'index.html'): void
    {
        if (!self::isAdmin()) {
            header("Location: {$redirect}?error=unauthorized_admin");
            exit();
        }
    }
}
