<?php

declare(strict_types=1);

if (!function_exists('current_user')) {
    function current_user(): ?array
    {
        return $_SESSION['sbe_auth_user'] ?? null;
    }
}

if (!function_exists('auth_login')) {
    function auth_login(array $user): void
    {
        $_SESSION['sbe_auth_user'] = [
            'auth_id' => (int) $user['auth_id'],
            'role' => (string) $user['role'],
            'login_id' => (string) $user['login_id'],
            'display_name' => (string) $user['display_name'],
            'teacher_id' => isset($user['teacher_id']) ? (int) $user['teacher_id'] : 0,
            'student_id' => isset($user['student_id']) ? (int) $user['student_id'] : 0,
            'status' => (string) $user['status'],
        ];
    }
}

if (!function_exists('auth_logout')) {
    function auth_logout(): void
    {
        unset($_SESSION['sbe_auth_user']);
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_regenerate_id(true);
        }
    }
}

if (!function_exists('require_login')) {
    function require_login(array $roles = []): void
    {
        $user = current_user();

        if (!$user) {
            header('Location: login.php');
            exit;
        }

        if ($roles && !in_array($user['role'], $roles, true)) {
            header('Location: index.php');
            exit;
        }
    }
}

if (!function_exists('is_teacher')) {
    function is_teacher(): bool
    {
        return (current_user()['role'] ?? null) === 'Teacher';
    }
}

if (!function_exists('is_student')) {
    function is_student(): bool
    {
        return (current_user()['role'] ?? null) === 'Student';
    }
}
