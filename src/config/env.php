<?php

$keys = ['DB_HOST', 'DB_DATABASE', 'DB_USERNAME', 'DB_PASSWORD', 'DB_CHARSET'];

foreach ($keys as $key) {
    $value = getenv($key);
    if ($value !== false) {
        $_ENV[$key] = $value;
    }
}