<?php

declare(strict_types=1);

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';

spl_autoload_register(static function (string $className): void {
    $baseDirs = [
        __DIR__ . '/models/',
        __DIR__ . '/daos/',
        __DIR__ . '/controllers/',
    ];

    foreach ($baseDirs as $baseDir) {
        $file = $baseDir . $className . '.php';
        if (is_file($file)) {
            require_once $file;
            return;
        }
    }
});

function format_app_date(?string $value, bool $includeTime = false): string
{
    $dateValue = trim((string) $value);
    if ($dateValue === '') {
        return '';
    }

    $timestamp = strtotime($dateValue);
    if ($timestamp === false) {
        return $dateValue;
    }

    return date($includeTime ? 'd-m-Y H:i' : 'd-m-Y', $timestamp);
}

require_once __DIR__ . '/auth.php';
