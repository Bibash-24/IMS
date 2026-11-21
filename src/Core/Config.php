<?php
declare(strict_types=1);

namespace IMS\Core;

/**
 * Enterprise Configuration & Environment Settings
 */
class Config
{
    private static array $settings = [
        'app_name' => 'WheelsOnDemand IMS - Enterprise Inventory Management System',
        'version' => '2.5.0-Enterprise',
        'env' => 'production',
        'debug' => false,
        'db' => [
            'host' => '127.0.0.1',
            'port' => 3306,
            'dbname' => 'ims',
            'username' => 'root',
            'password' => '',
            'charset' => 'utf8mb4'
        ],
        'tax' => [
            'default_vat_rate' => 0.13, // 13% Government VAT in Nepal
            'pan_required' => true,
            'pan_length' => 9
        ],
        'currency' => [
            'code' => 'NPR',
            'symbol' => 'NPR',
            'format' => 'NPR %s'
        ],
        'security' => [
            'session_lifetime' => 7200, // 2 hours
            'rate_limit_window' => 60,   // seconds
            'rate_limit_max' => 30,      // attempts per window
            'csrf_token_ttl' => 3600     // 1 hour
        ]
    ];

    public static function get(string $key, mixed $default = null): mixed
    {
        $keys = explode('.', $key);
        $current = self::$settings;

        foreach ($keys as $k) {
            if (!is_array($current) || !array_key_exists($k, $current)) {
                return $default;
            }
            $current = $current[$k];
        }

        return $current;
    }

    public static function set(string $key, mixed $value): void
    {
        $keys = explode('.', $key);
        $current = &self::$settings;

        foreach ($keys as $k) {
            if (!isset($current[$k]) || !is_array($current[$k])) {
                $current[$k] = [];
            }
            $current = &$current[$k];
        }

        $current = $value;
    }

    public static function isProduction(): bool
    {
        return self::get('env') === 'production';
    }
}
