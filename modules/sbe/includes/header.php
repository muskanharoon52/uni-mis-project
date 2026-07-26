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
                <a class="<?= $activePage === 'dashboard' ? 'active' : '' ?>" href="index.php">
                    <span class="nav-icon">&#127968;</span> Dashboard
                </a>
                <a class="<?= $activePage === 'login' ? 'active' : '' ?>" href="login.php">
                    <span class="nav-icon">&#128273;</span> Sign In
                </a>
            <?php elseif ($userRole === 'Teacher'): ?>
                <span class="nav-section-label">Overview</span>
                <a class="<?= $activePage === 'dashboard' ? 'active' : '' ?>" href="teacher-home.php">
                    <span class="nav-icon">&#127968;</span> Dashboard
                </a>
                
                <span class="nav-section-label">Exam Builder</span>
                <a class="<?= $activePage === 'question_bank' ? 'active' : '' ?>" href="question-bank.php">
                    <span class="nav-icon">&#128218;</span> Question Pool
                </a>
                <a class="<?= $activePage === 'exams' ? 'active' : '' ?>" href="exams.php">
                    <span class="nav-icon">&#128221;</span> Exam Pool
                </a>
                <a class="<?= $activePage === 'exam_schedule' ? 'active' : '' ?>" href="exam-schedule.php">
                    <span class="nav-icon">&#128197;</span> Exam Schedule
                </a>
                <a class="<?= $activePage === 'exam_questions' ? 'active' : '' ?>" href="exam-questions.php">
                    <span class="nav-icon">&#128450;</span> Question Mapping
                </a>
                
                <span class="nav-section-label">Grading & Audits</span>
                <a class="<?= $activePage === 'student_exams' ? 'active' : '' ?>" href="student-exams.php">
                    <span class="nav-icon">&#128101;</span> Student Attempts
                </a>
                <a class="<?= $activePage === 'student_answers' ? 'active' : '' ?>" href="student-answers.php">
                    <span class="nav-icon">&#9997;</span> Answer Audits
                </a>
                <a class="<?= $activePage === 'exam_results' ? 'active' : '' ?>" href="exam-results.php">
                    <span class="nav-icon">&#127942;</span> Final Grades
                </a>
            <?php elseif ($userRole === 'Student'): ?>
                <span class="nav-section-label">Student</span>
                <a class="<?= $activePage === 'dashboard' ? 'active' : '' ?>" href="student-home.php">
                    <span class="nav-icon">&#127968;</span> My Dashboard
                </a>
                <a class="<?= $activePage === 'student_start_exam' ? 'active' : '' ?>" href="student-start-exam.php">
                    <span class="nav-icon">&#9654;</span> Start Exam
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
