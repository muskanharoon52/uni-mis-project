<?php

declare(strict_types=1);

require dirname(__DIR__, 1) . '/config/sbe_db_connect.php';
require __DIR__ . '/includes/helpers.php';
require __DIR__ . '/includes/auth.php';

$user = current_user();

if ($user) {
    redirect($user['role'] === 'Student' ? 'student-home.php' : 'teacher-home.php');
} else {
    redirect('login.php');
}
