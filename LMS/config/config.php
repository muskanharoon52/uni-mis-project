<?php

declare(strict_types=1);

define('APP_NAME', 'University LMS');
define('APP_BASE_PATH', '/LMS');

define('DB_HOST', '127.0.0.1');
define('DB_NAME', 'lms_mis');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

function app_url(string $path = ''): string
{
    return APP_BASE_PATH . '/' . ltrim($path, '/');
}

function asset_url(string $path): string
{
    return app_url('public/assets/' . ltrim($path, '/'));
}
