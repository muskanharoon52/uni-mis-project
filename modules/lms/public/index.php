<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';

$user = current_user();

if ($user) {
    redirect_to_dashboard($user);
}

header('Location: ' . app_url('public/login.php'));
exit;
