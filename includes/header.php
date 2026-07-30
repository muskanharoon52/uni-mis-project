<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/db_connect.php';
require_once __DIR__ . '/../modules/sso/includes/auth.php';

if (!isLoggedIn()) {
    header('Location: /uni-mis-project/');
    exit();
}

$user = getCurrentUser();
$pageTitle = $pageTitle ?? 'Dashboard';
$currentPage = basename($_SERVER['PHP_SELF']);
$currentFolder = basename(dirname($_SERVER['PHP_SELF']));
$userName = $_SESSION['full_name'] ?? 'User';
$userInitial = strtoupper(substr($userName, 0, 1));
$roleName = ucfirst($_SESSION['role_name'] ?? 'User');
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($pageTitle) ?> | University MIS</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="/uni-mis-project/modules/lms/public/assets/style.css?v=<?= @filemtime($_SERVER['DOCUMENT_ROOT'] . '/uni-mis-project/modules/lms/public/assets/style.css') ?>">
</head>
<body>
<button class="menu-toggle" id="menu-toggle">&#9776;</button>
<div class="sidebar-overlay" id="sidebar-overlay"></div>

<div class="app-shell">
    <aside class="sidebar" id="sidebar">
        <a href="/uni-mis-project/dashboard.php" class="brand">
            <div class="brand-mark">MIS</div>
            <div>
                <h1>University MIS</h1>
                <p>SSO Module</p>
            </div>
        </a>

        <nav class="nav">
            <span class="nav-section-label">Overview</span>
            <a class="<?= $currentPage === 'dashboard.php' ? 'active' : '' ?>" href="/uni-mis-project/dashboard.php">
                Dashboard
            </a>

            <span class="nav-section-label">Student Management</span>
            <a class="<?= $currentFolder === 'students' ? 'active' : '' ?>" href="/uni-mis-project/students/index.php">
                Students
            </a>
            <a class="<?= $currentFolder === 'student_enrollment' ? 'active' : '' ?>" href="/uni-mis-project/student_enrollment/index.php">
                Enrollment
            </a>

            <span class="nav-section-label">Academics</span>
            <a class="<?= $currentFolder === 'Courses' ? 'active' : '' ?>" href="/uni-mis-project/Courses/index.php">
                Courses
            </a>
            <a class="<?= $currentFolder === 'semester_courses' ? 'active' : '' ?>" href="/uni-mis-project/semester_courses/index.php">
                Semester Courses
            </a>
            <a class="<?= $currentFolder === 'teacher_assignment' ? 'active' : '' ?>" href="/uni-mis-project/teacher_assignment/index.php">
                Teachers
            </a>
            <a class="<?= $currentFolder === 'TImetable' ? 'active' : '' ?>" href="/uni-mis-project/TImetable/index.php">
                Timetable
            </a>
            <a class="<?= $currentFolder === 'attendance' ? 'active' : '' ?>" href="/uni-mis-project/attendance/index.php">
                Attendance
            </a>
            
            <a class="<?= $currentPage === 'lms_applications.php' ? 'active' : '' ?>" href="/uni-mis-project/lms_applications.php">
                Application
            </a>

            <div class="spacer"></div>

            <?php
            $isSuper = strtolower($_SESSION['role_name'] ?? '') === 'super admin';
            $isAdmin = strtolower($_SESSION['role_name'] ?? '') === 'admin';
            if ($isSuper || $isAdmin):
            ?>
            <span class="nav-section-label">Administration</span>
            <a class="<?= $currentFolder === 'admin' ? 'active' : '' ?>" href="/uni-mis-project/modules/admin/users.php">
                User Management
            </a>
            <?php endif; ?>

            <a href="/uni-mis-project/logout.php" class="sidebar-logout-btn">
                Logout
            </a>
        </nav>
    </aside>

    <main class="content">
        <div class="topbar">
            <div>
                <span class="eyebrow">SSO Module</span>
                <h2><?= htmlspecialchars($pageTitle) ?></h2>
            </div>
            <div class="topbar-actions">
                <div class="topbar-user-dropdown">
                    <button class="topbar-user-btn">
                        <span class="topbar-user-avatar"><?= htmlspecialchars($userInitial) ?></span>
                        <span class="topbar-user-name"><?= htmlspecialchars($userName) ?></span>
                        <span class="topbar-chevron">&#9662;</span>
                    </button>
                    <div class="topbar-dropdown-menu">
                        <span style="display:block;padding:6px 16px;font-size:12px;color:var(--muted);"><?= htmlspecialchars($roleName) ?></span>
                        <a href="/uni-mis-project/logout.php">&#x2190; Logout</a>
                    </div>
                </div>
            </div>
        </div>
