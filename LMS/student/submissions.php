<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';

require_role('student');
header('Location: ' . app_url('student/courses.php?view=assignment'));
exit;
