<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

$envFile = __DIR__ . '/../.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (str_starts_with(trim($line), '#')) {
            continue;
        }
        if (str_contains($line, '=')) {
            [$name, $value] = explode('=', $line, 2);
            $name = trim($name);
            $value = trim($value);
            if ($name !== '' && !isset($_ENV[$name]) && !isset($_SERVER[$name])) {
                $_ENV[$name] = $value;
                $_SERVER[$name] = $value;
                putenv("{$name}={$value}");
            }
        }
    }
}
