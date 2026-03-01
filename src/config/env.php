<?php

$envPath = __DIR__ . '/../../.env';

if (!file_exists($envPath)) {
    throw new RuntimeException('.env file not found');
}

$lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

foreach ($lines as $line) {
    if (str_starts_with(trim($line), '#')) {
        continue; // skip comments
    }

    [$key, $value] = array_pad(explode('=', $line, 2), 2, '');

    $key = trim($key);
    $value = trim($value);

    // Remove quotes
    $value = trim($value, '"\'');

    $_ENV[$key] = $value;
    putenv("$key=$value");
}