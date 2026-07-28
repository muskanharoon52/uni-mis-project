<?php

declare(strict_types=1);

ini_set('display_errors', '0');
ini_set('log_errors', '1');
error_reporting(E_ALL);

define('APP_NAME', 'University LMS');
define('APP_BASE_PATH', '/uni-mis-project/modules/lms');

define('DB_HOST', '127.0.0.1');
define('DB_NAME', 'university_mis');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

function e(?string $s): string
{
    return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8');
}

function app_url(string $path = ''): string
{
    return APP_BASE_PATH . '/' . ltrim($path, '/');
}

function asset_url(string $path): string
{
    return app_url('public/assets/' . ltrim($path, '/'));
}

function old(string $key, string $default = ''): string
{
    return e($_POST[$key] ?? $default);
}

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

function csrf_field(): string
{
    return '<input type="hidden" name="_csrf_token" value="' . csrf_token() . '">';
}

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

function redirect_to_dashboard(array $user): void
{
    $path = ($user['role'] ?? '') === 'teacher'
        ? 'teacher/dashboard.php'
        : 'student/dashboard.php';
    header('Location: ' . app_url($path));
    exit;
}

function login_user(array $user): void
{
    require_once __DIR__ . '/../includes/auth.php';
    auth_login($user);
}

function logout_user(): void
{
    require_once __DIR__ . '/../includes/auth.php';
    auth_logout();
}
