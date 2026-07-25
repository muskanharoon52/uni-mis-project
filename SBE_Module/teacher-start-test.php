<?php

declare(strict_types=1);

require dirname(__DIR__, 1) . '/config/sbe_db_connect.php';
require __DIR__ . '/includes/helpers.php';
require __DIR__ . '/includes/auth.php';

require_login(['Teacher']);

redirect('exam-schedule.php');
