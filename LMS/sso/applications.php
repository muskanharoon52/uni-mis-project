<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';

$user = require_login();

if ($user['role'] === 'teacher') {
    header('Location: ' . app_url('teacher/applications.php'));
    exit;
}

header('Location: ' . app_url('student/applications.php'));
exit;
