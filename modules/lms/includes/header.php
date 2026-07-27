<?php

declare(strict_types=1);

$pageTitle = $pageTitle ?? APP_NAME;
$active = $active ?? '';
$user = $user ?? current_user();
$role = $user['role'] ?? null;
$userInitial = strtoupper(substr($user['name'] ?? 'G', 0, 1));

$teacherLinks = [
    'dashboard'    => ['Dashboard', 'teacher/dashboard.php', '&#127968;'],
    'courses'      => ['My Courses', 'teacher/courses.php', '&#128218;'],
    'attendance'   => ['Attendance', 'teacher/attendance.php', '&#128197;'],
    'assignments'  => ['Assignments', 'teacher/assignments.php', '&#128221;'],
    'grading'      => ['Grading', 'teacher/grading.php', '&#9997;'],
    'internal_marks' => ['Internal Marks', 'teacher/internal_marks.php', '&#128200;'],
    'materials'    => ['Course Materials', 'teacher/course_materials.php', '&#128214;'],
    'announcements'=> ['Announcements', 'teacher/announcements.php', '&#128227;'],
    'messages'     => ['Messages', 'teacher/messages.php', '&#128172;'],
    'queries'      => ['Queries', 'teacher/queries.php', '&#10067;'],
    'applications' => ['Applications', 'teacher/applications.php', '&#128203;'],
    'settings'     => ['Settings', 'teacher/settings.php', '&#9881;'],
];

$studentLinks = [
    'dashboard'    => ['Dashboard', 'student/dashboard.php', '&#127968;'],
    'courses'      => ['Courses', 'student/courses.php', '&#128218;'],
    'attendance'   => ['Attendance', 'student/attendance.php', '&#128197;'],
    'marks'        => ['Internal Marks', 'student/marks.php', '&#128200;'],
    'fees'         => ['Fees', 'student/fees.php', '&#128176;'],
    'announcements'=> ['Announcements', 'student/announcements.php', '&#128227;'],
    'messages'     => ['Messages', 'student/messages.php', '&#128172;'],
    'queries'      => ['Queries', 'student/queries.php', '&#10067;'],
    'applications' => ['Applications', 'student/applications.php', '&#128203;'],
    'profile'      => ['Profile', 'student/profile.php', '&#128100;'],
];

$links = $role === 'teacher' ? $teacherLinks : ($role === 'student' ? $studentLinks : []);
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($pageTitle) ?> | <?= APP_NAME ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= asset_url('style.css') ?>?v=<?= filemtime(__DIR__ . '/../public/assets/style.css') ?>">
</head>
<body>
<button class="menu-toggle" id="menu-toggle">&#9776;</button>
<div class="sidebar-overlay" id="sidebar-overlay"></div>

<div class="app-shell">
    <aside class="sidebar" id="sidebar">
        <!-- Brand/Logo links to root index.php -->
        <a href="<?= app_url('index.php') ?>" class="brand" style="text-decoration: none; display: flex; align-items: center; gap: 0.75rem;">
            <div class="brand-mark">LMS</div>
            <div>
                <h1><?= APP_NAME ?></h1>
                <p>University ERP</p>
            </div>
        </a>

        <nav class="nav">
            <?php if ($role === 'teacher'): ?>
                <span class="nav-section-label">Overview</span>
                <a class="<?= $active === 'dashboard' ? 'active' : '' ?>" href="<?= app_url('teacher/dashboard.php') ?>">
                    <span class="nav-icon">&#127968;</span> Dashboard
                </a>

                <span class="nav-section-label">Academics</span>
                <a class="<?= $active === 'courses' ? 'active' : '' ?>" href="<?= app_url('teacher/courses.php') ?>">
                    <span class="nav-icon">&#128218;</span> My Courses
                </a>
                <a class="<?= $active === 'attendance' ? 'active' : '' ?>" href="<?= app_url('teacher/attendance.php') ?>">
                    <span class="nav-icon">&#128197;</span> Attendance
                </a>
                <a class="<?= $active === 'assignments' ? 'active' : '' ?>" href="<?= app_url('teacher/assignments.php') ?>">
                    <span class="nav-icon">&#128221;</span> Assignments
                </a>

                <a class="<?= $active === 'timetable' ? 'active' : '' ?>" href="<?= app_url('teacher/timetable.php') ?>">
                    <span class="nav-icon">&#128197;</span> Timetable
                </a>

                <span class="nav-section-label">Grading</span>
                <a class="<?= $active === 'grading' ? 'active' : '' ?>" href="<?= app_url('teacher/grading.php') ?>">
                    <span class="nav-icon">&#9997;</span> Grading
                </a>
                <a class="<?= $active === 'internal_marks' ? 'active' : '' ?>" href="<?= app_url('teacher/internal_marks.php') ?>">
                    <span class="nav-icon">&#128200;</span> Internal Marks
                </a>

                <span class="nav-section-label">Communication</span>
                <a class="<?= $active === 'announcements' ? 'active' : '' ?>" href="<?= app_url('teacher/announcements.php') ?>">
                    <span class="nav-icon">&#128227;</span> Announcements
                </a>
                <a class="<?= $active === 'messages' ? 'active' : '' ?>" href="<?= app_url('teacher/messages.php') ?>">
                    <span class="nav-icon">&#128172;</span> Messages
                </a>
                <a class="<?= $active === 'queries' ? 'active' : '' ?>" href="<?= app_url('teacher/queries.php') ?>">
                    <span class="nav-icon">&#10067;</span> Queries
                </a>
                <a class="<?= $active === 'applications' ? 'active' : '' ?>" href="<?= app_url('teacher/applications.php') ?>">
                    <span class="nav-icon">&#128203;</span> Applications
                </a>
            <?php elseif ($role === 'student'): ?>
                <span class="nav-section-label">Overview</span>
                <a class="<?= $active === 'dashboard' ? 'active' : '' ?>" href="<?= app_url('student/dashboard.php') ?>">
                    <span class="nav-icon">&#127968;</span> Dashboard
                </a>

                <span class="nav-section-label">Academics</span>
                <a class="<?= $active === 'courses' ? 'active' : '' ?>" href="<?= app_url('student/courses.php') ?>">
                    <span class="nav-icon">&#128218;</span> Courses
                </a>
                <a class="<?= $active === 'attendance' ? 'active' : '' ?>" href="<?= app_url('student/attendance.php') ?>">
                    <span class="nav-icon">&#128197;</span> Attendance
                </a>
                <a class="<?= $active === 'marks' ? 'active' : '' ?>" href="<?= app_url('student/marks.php') ?>">
                    <span class="nav-icon">&#128200;</span> Internal Marks
                </a>
                <a class="<?= $active === 'timetable' ? 'active' : '' ?>" href="<?= app_url('student/timetable.php') ?>">
                    <span class="nav-icon">&#128197;</span> Timetable
                </a>

                <span class="nav-section-label">Finance</span>
                <a class="<?= $active === 'fees' ? 'active' : '' ?>" href="<?= app_url('student/fees.php') ?>">
                    <span class="nav-icon">&#128176;</span> Fees
                </a>

                <span class="nav-section-label">Communication</span>
                <a class="<?= $active === 'announcements' ? 'active' : '' ?>" href="<?= app_url('student/announcements.php') ?>">
                    <span class="nav-icon">&#128227;</span> Announcements
                </a>
                <a class="<?= $active === 'messages' ? 'active' : '' ?>" href="<?= app_url('student/messages.php') ?>">
                    <span class="nav-icon">&#128172;</span> Messages
                </a>
                <a class="<?= $active === 'queries' ? 'active' : '' ?>" href="<?= app_url('student/queries.php') ?>">
                    <span class="nav-icon">&#10067;</span> Queries
                </a>
                <a class="<?= $active === 'applications' ? 'active' : '' ?>" href="<?= app_url('student/applications.php') ?>">
                    <span class="nav-icon">&#128203;</span> Applications
                </a>
            <?php endif; ?>
        </nav>
    </aside>

    <main class="content">
        <div class="topbar">
            <div>
                <span class="eyebrow"><?= APP_NAME ?></span>
                <h2><?= e($pageTitle) ?></h2>
            </div>
            <div class="topbar-actions">
                <?php if ($user): ?>
                    <?php
                        $msgCount = unread_notification_count((int) $user['id'], 'message');
                        $annCount = unread_notification_count((int) $user['id'], 'announcement');
                        $prefix = $role === 'teacher' ? 'teacher' : 'student';
                    ?>
                    <a class="topbar-icon-btn" href="<?= app_url($prefix . '/messages.php') ?>" title="Messages">
                        &#128172;
                        <?php if ($msgCount > 0): ?><span class="topbar-badge"><?= $msgCount ?></span><?php endif; ?>
                    </a>
                    <a class="topbar-icon-btn" href="<?= app_url($prefix . '/announcements.php') ?>" title="Announcements">
                        &#128276;
                        <?php if ($annCount > 0): ?><span class="topbar-badge"><?= $annCount ?></span><?php endif; ?>
                    </a>
                    <div class="topbar-user-dropdown">
                        <button class="topbar-user-btn">
                            <span class="topbar-user-avatar"><?= e($userInitial) ?></span>
                            <span class="topbar-user-name"><?= e($user['name']) ?></span>
                            <span class="topbar-chevron">&#9662;</span>
                        </button>
                        <div class="topbar-dropdown-menu">
                            <a href="<?= app_url($prefix . '/profile.php') ?>">&#128100; Profile</a>
                            <a href="/uni-mis-project/logout.php">&#x2192; Logout</a>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>