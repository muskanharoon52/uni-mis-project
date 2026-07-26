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
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($pageTitle) ?> | <?= e($config['app_name']) ?></title>
    <meta name="description" content="Examination Portal — <?= e($pageTitle) ?>">
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
            <div class="brand-mark">EXM</div>
            <div>
                <h1><?= e($config['app_name']) ?></h1>
                <p>University ERP</p>
            </div>
        </div>

        <nav class="nav">
            <?php if ($userRole === 'guest'): ?>
                <span class="nav-section-label">Portal</span>
                <a class="<?= $activePage === 'dashboard' ? 'active' : '' ?>" href="index.php">
                    <span class="nav-icon">&#127968;</span> Dashboard
                </a>
                <a class="<?= $activePage === 'login' ? 'active' : '' ?>" href="login.php">
                    <span class="nav-icon">&#128273;</span> Sign In
                </a>
            <?php elseif ($userRole === 'Examiner'): ?>
                <span class="nav-section-label">Overview</span>
                <a class="<?= $activePage === 'dashboard' ? 'active' : '' ?>" href="index.php">
                    <span class="nav-icon">&#127968;</span> Dashboard
                </a>

                <span class="nav-section-label">Results &amp; Grading</span>
                <a class="<?= $activePage === 'view_results' ? 'active' : '' ?>" href="view-results.php">
                    <span class="nav-icon">&#127942;</span> View Results
                </a>

                <span class="nav-section-label">Exam Management</span>
                <a class="<?= $activePage === 'exam_schedule' ? 'active' : '' ?>" href="exam-schedule.php">
                    <span class="nav-icon">&#128197;</span> Exam Schedule
                </a>

                <span class="nav-section-label">Student Affairs</span>
                <a class="<?= $activePage === 'promote_students' ? 'active' : '' ?>" href="promote-students.php">
                    <span class="nav-icon">&#11014;</span> Promote Students
                </a>
            <?php endif; ?>
        </nav>

        <div class="sidebar-user">
            <?php if ($user): ?>
                <div class="user-strip">
                    <div class="user-strip-avatar"><?= e($userInitial) ?></div>
                    <div class="user-strip-info">
                        <span class="user-strip-name"><?= e($user['display_name']) ?></span>
                        <span class="user-strip-role"><?= e($user['role']) ?> &middot; <?= e($user['login_id']) ?></span>
                    </div>
                </div>
                <div class="actions">
                    <a class="btn btn-ghost btn-sm" href="profile.php">&#9881; Profile</a>
                    <a class="btn btn-ghost btn-sm" href="logout.php">Logout</a>
                </div>
            <?php else: ?>
                <div class="user-strip">
                    <div class="user-strip-avatar">?</div>
                    <div class="user-strip-info">
                        <span class="user-strip-name">Not signed in</span>
                        <span class="user-strip-role">guest</span>
                    </div>
                </div>
                <a class="btn btn-primary" href="login.php" style="width:100%; justify-content:center;">Sign In</a>
            <?php endif; ?>
        </div>
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
                    <a class="btn btn-ghost btn-sm" href="login.php">Sign In</a>
                <?php endif; ?>
            </div>
        </div>
