<?php
require_once __DIR__ . '/includes/auth.php';
sa_guard();
header('Location: dashboard.php');
exit;
