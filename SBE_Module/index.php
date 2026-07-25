<?php

declare(strict_types=1);

require __DIR__ . '/config/database.php';
require __DIR__ . '/includes/helpers.php';
require __DIR__ . '/includes/auth.php';

$user = current_user();

if ($user) {
    redirect($user['role'] === 'Student' ? 'student-home.php' : 'teacher-home.php');
} else {
    redirect('login.php');
}
