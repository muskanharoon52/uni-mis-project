<?php

declare(strict_types=1);

if (!function_exists('current_user')) {
    function current_user(): ?array
    {
        if (isset($_SESSION['auth_user'])) {
            return $_SESSION['auth_user'];
        }

        // Allow the portal Super Admin to view SBE dashboards without a separate SBE login.
        if (!empty($_SESSION['user_id']) && strtolower((string) ($_SESSION['role_name'] ?? '')) === 'super admin') {
            return [
                'auth_id'     => (int) $_SESSION['user_id'],
                'role'        => 'super admin',
                'login_id'    => (string) ($_SESSION['username'] ?? ''),
                'display_name'=> (string) ($_SESSION['full_name'] ?? 'Super Admin'),
                'teacher_id'  => 0,
                'student_id'  => 0,
                'status'      => 'Active',
            ];
        }

        return null;
    }
}

if (!function_exists('auth_login')) {
    function auth_login(array $user): void
    {
        $_SESSION['auth_user'] = [
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
        unset($_SESSION['auth_user']);
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_regenerate_id(true);
        }
    }
}

if (!function_exists('is_portal_super_admin')) {
    function is_portal_super_admin(): bool
    {
        return (int) ($_SESSION['role_id'] ?? 0) === 7
            || strtolower((string) ($_SESSION['role_name'] ?? '')) === 'super admin';
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

        if ($roles && !is_portal_super_admin() && !in_array($user['role'], $roles, true)) {
            header('Location: index.php');
            exit;
        }

        // Portal Super Admin: take on the page's role so the SBE side panel
        // renders the matching links for the dashboard they entered from.
        if (is_portal_super_admin() && $roles) {
            $_SESSION['auth_user'] = [
                'auth_id'      => (int) $user['auth_id'],
                'role'         => $roles[0],
                'login_id'     => (string) ($user['login_id'] ?? ''),
                'display_name' => (string) ($user['display_name'] ?? 'Super Admin'),
                'teacher_id'   => 0,
                'student_id'   => 0,
                'status'       => 'Active',
            ];
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
