<?php
declare(strict_types=1);

/**
 * Enterprise PSR-4 Autoloader for IMS Framework
 * Namespace: IMS\
 * Maps to: __DIR__ /
 */
spl_autoload_register(function (string $class): void {
    $prefix = 'IMS\\';
    $baseDir = __DIR__ . '/';

    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }

    $relativeClass = substr($class, $len);
    $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';

    if (file_exists($file)) {
        require_once $file;
    }
});
