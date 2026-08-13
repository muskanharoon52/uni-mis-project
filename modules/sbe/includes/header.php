<?php

declare(strict_types=1);

$authFile = __DIR__ . '/auth.php';
if (file_exists($authFile)) {
    require_once $authFile;
}

$config = require __DIR__ . '/../config/app.php';
$pageTitle = $pageTitle ?? $config['app_name'];
$activePage = $activePage ?? 'dashboard';
$user = current_user();
$userRole = $user['role'] ?? 'guest';
$userInitial = strtoupper(substr($user['display_name'] ?? 'G', 0, 1));
// Base URL for this module pages (absolute path). When the config value is empty,
// derive it from the currently executing script so every sidebar link resolves to
// this module's folder regardless of where the project is installed.
$baseUrl = trim((string) ($config['base_url'] ?? ''));
if ($baseUrl === '') {
    $scriptDir = rtrim(str_replace('\\', '/', dirname((string) ($_SERVER['SCRIPT_NAME'] ?? '/uni-mis-project/modules/sbe/'))), '/');
    $baseUrl = ($scriptDir !== '' && $scriptDir !== '/') ? $scriptDir . '/' : '/uni-mis-project/modules/sbe/';
}
$basePath = rtrim($baseUrl, '/') . '/';
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($pageTitle) ?> | <?= e($config['app_name']) ?></title>
    <meta name="description" content="System Based Examination — <?= e($pageTitle) ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<button class="menu-toggle" id="menu-toggle">&#9776;</button>
<div class="sidebar-overlay" id="sidebar-overlay"></div>

<div class="app-shell">
    <aside class="sidebar" id="sidebar">
        <div class="brand">
            <div class="brand-mark">SBE</div>
            <div>
                <h1><?= e($config['app_name']) ?></h1>
                <p>University ERP</p>
            </div>
        </div>

        <nav class="nav">
            <?php if ($userRole === 'guest'): ?>
                <span class="nav-section-label">Portal</span>
                <a class="<?= $activePage === 'dashboard' ? 'active' : '' ?>" href="<?= $basePath ?>index.php">Dashboard</a>
                <a class="<?= $activePage === 'login' ? 'active' : '' ?>" href="<?= $basePath ?>login.php">Sign In</a>
            <?php elseif ($userRole === 'Teacher'): ?>
                <span class="nav-section-label">Overview</span>
                <a class="<?= $activePage === 'dashboard' ? 'active' : '' ?>" href="<?= $basePath ?>teacher-home.php">Dashboard</a>
                
                <span class="nav-section-label">Exam Builder</span>
                <a class="<?= $activePage === 'exams' ? 'active' : '' ?>" href="<?= $basePath ?>exams.php">Create Exam</a>

                <span class="nav-section-label">Scheduling</span>
                <a class="<?= $activePage === 'exam_schedule' ? 'active' : '' ?>" href="<?= $basePath ?>schedule.php">Schedule</a>
                <a class="<?= $activePage === 'datesheets' ? 'active' : '' ?>" href="<?= $basePath ?>datesheets.php">Datesheets</a>

                <span class="nav-section-label">Results</span>
                <a class="<?= $activePage === 'view_results' ? 'active' : '' ?>" href="<?= $basePath ?>view-results.php">View Results</a>

                <?php
                require_once __DIR__ . '/../../../includes/sa_submenu.php';
                sa_render_submodules('sbe', ['teacher_home', 'exam_schedule', 'datesheets', 'exam_results', 'student_home', 'student_exams']);
                ?>
            <?php elseif ($userRole === 'Student'): ?>
                <?php
                require_once __DIR__ . '/../../../includes/sa_submenu.php';
                sa_render_submodules('sbe', ['teacher_home', 'exam_schedule', 'datesheets', 'question_bank', 'exam_results']);
                ?>
            <?php endif; ?>
        </nav>

        <div class="sidebar-logout">
                <a class="sidebar-logout-btn" href="<?= $basePath ?>logout.php">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </div>

            <?php if ($user): ?>
                <div class="user-strip">
                    <div class="user-strip-avatar"><?= e($userInitial) ?></div>
                    <div class="user-strip-info">
                        <span class="user-strip-name"><?= e($user['display_name']) ?></span>
                        <span class="user-strip-role"><?= e($user['role']) ?> &middot; <?= e($user['login_id']) ?></span>
                    </div>
                </div>
                <div class="actions">
                    <a class="btn btn-ghost btn-sm" href="<?= $basePath ?>profile.php">&#9881; Profile</a>
                    <a class="btn btn-ghost btn-sm" href="<?= $basePath ?>logout.php">&#x2192; Logout</a>
                </div>
            <?php else: ?>
                <div class="user-strip">
                    <div class="user-strip-avatar">?</div>
                    <div class="user-strip-info">
                        <span class="user-strip-name">Not signed in</span>
                        <span class="user-strip-role">guest</span>
                    </div>
                </div>
                <a class="btn btn-primary" href="<?= $basePath ?>login.php" style="width:100%; justify-content:center;">Sign In</a>
            <?php endif; ?>
    </aside>

    <main class="content">
        <div class="topbar">
            <div>
                <span class="eyebrow"><?= e($config['app_name']) ?></span>
                <h2><?= e($pageTitle) ?></h2>
            </div>
            <div class="topbar-actions">
                <?php if ($user): ?>
                    <span class="badge badge-<?= e(strtolower($user['role'])) ?>"><?= e($user['role']) ?></span>
                    <span class="topbar-user"><?= e($user['display_name']) ?></span>
                <?php else: ?>
                    <a class="btn btn-ghost btn-sm" href="<?= $basePath ?>login.php">Sign In</a>
                <?php endif; ?>
            </div>
        </div>
