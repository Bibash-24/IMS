<?php
declare(strict_types=1);

namespace IMS\Security;

/**
 * Enterprise Input Sanitization & Validation Engine
 */
class InputValidator
{
    public static function sanitizeString(?string $input): string
    {
        if ($input === null) {
            return '';
        }
        return trim(strip_tags($input));
    }

    public static function sanitizeHtml(?string $input): string
    {
        if ($input === null) {
            return '';
        }
        return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
    }

    public static function validateEmail(string $email): bool
    {
        return (bool) filter_var(trim($email), FILTER_VALIDATE_EMAIL);
    }

    public static function validateNepaliPhone(string $phone): bool
    {
        // 10 digits starting with 98 or 97 or landline 01
        $clean = preg_replace('/[^\d]/', '', trim($phone));
        return (bool) preg_match('/^(98\d{8}|97\d{8}|01\d{7})$/', $clean);
    }

    public static function validatePanNumber(string $pan): bool
    {
        // Nepali PAN is exactly 9 digits
        return (bool) preg_match('/^\d{9}$/', trim($pan));
    }

    public static function validateSku(string $sku): bool
    {
        // Alphanumeric, hyphens and underscores, 3 to 30 chars
        return (bool) preg_match('/^[A-Za-z0-9\-_]{3,30}$/', trim($sku));
    }

    public static function validatePositiveFloat(mixed $value): ?float
    {
        if (!is_numeric($value)) {
            return null;
        }
        $f = (float) $value;
        return $f >= 0 ? $f : null;
    }

    public static function validatePositiveInt(mixed $value): ?int
    {
        if (!is_numeric($value)) {
            return null;
        }
        $i = (int) $value;
        return $i >= 0 ? $i : null;
    }
}
