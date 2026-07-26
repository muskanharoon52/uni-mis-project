<?php

declare(strict_types=1);

if (!function_exists('e')) {
    function e(?string $value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('redirect')) {
    function redirect(string $path): never
    {
        header('Location: ' . $path);
        exit;
    }
}

if (!function_exists('old')) {
    function old(array $data, string $key, mixed $default = ''): mixed
    {
        return array_key_exists($key, $data) ? $data[$key] : $default;
    }
}

if (!function_exists('table_count')) {
    function table_count(string $table): int
    {
        $stmt = db()->query('SELECT COUNT(*) AS total FROM ' . $table);
        return (int) $stmt->fetchColumn();
    }
}
