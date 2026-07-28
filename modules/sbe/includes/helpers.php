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

if (!function_exists('csrf_token')) {
    function csrf_token(): string
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (empty($_SESSION['_csrf_token'])) {
            $_SESSION['_csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['_csrf_token'];
    }
}

if (!function_exists('csrf_field')) {
    function csrf_field(): string
    {
        return '<input type="hidden" name="_csrf_token" value="' . csrf_token() . '">';
    }
}

if (!function_exists('verify_csrf')) {
    function verify_csrf(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return;
        }
        $token = $_POST['_csrf_token'] ?? '';
        if (!hash_equals(csrf_token(), $token)) {
            http_response_code(403);
            exit('Access denied.');
        }
    }
}
