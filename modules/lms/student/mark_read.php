<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/auth.php';

$user = require_login();
$role = strtolower($user['role'] ?? '');

if (!in_array($role, ['student', 'teacher'])) {
    header('Location: ' . app_url('public/login.php'));
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $notificationId = (int) ($_POST['notification_id'] ?? 0);
    if ($notificationId > 0) {
        $stmt = db()->prepare('UPDATE lms_notifications SET is_read = 1 WHERE notification_id = ? AND recipient_user_id = ?');
        $stmt->execute([$notificationId, $user['id']]);
    }
}

$redirect = $role === 'teacher' ? app_url('teacher/messages.php') : app_url('student/messages.php');
header('Location: ' . $redirect);
exit;
