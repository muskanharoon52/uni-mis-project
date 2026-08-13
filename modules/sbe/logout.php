<?php

declare(strict_types=1);

require __DIR__ . '/config/database.php';
require __DIR__ . '/includes/helpers.php';
require __DIR__ . '/includes/auth.php';

auth_logout();

// Fully clear the session (same convention as the main portal logout.php) so a
// Portal Super Admin session cannot synthesise an SBE login and bounce the user
// straight back in. End at the unified main portal login page.
$_SESSION = [];
if (session_status() === PHP_SESSION_ACTIVE) {
    session_destroy();
}

header('Location: ../../index.php');
exit;
