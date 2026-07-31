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
            <a class="<?= $currentFolder === 'student_inquiry' ? 'active' : '' ?>" href="/uni-mis-project/student_inquiry/index.php">
                Student Inquiry
            </a>

            <?php
            $acrKeys = [
                'section_change.php'    => 'Section Change Request',
                'department_transfer.php' => 'Department Transfer Request',
                'program_change.php'    => 'Program Change Request',
                'course_add_drop.php'   => 'Course Add/Drop Request',
                'course_withdrawal.php' => 'Course Withdrawal Request',
                'request_status.php'    => 'Request Status',
            ];
            $acrIsOpen = $currentFolder === 'academic_change_requests';
            ?>
            <div class="nav-group <?= $acrIsOpen ? 'open' : '' ?>" id="nav-acr">
                <a href="javascript:void(0);" class="nav-parent" onclick="toggleNavGroup('nav-acr'); return false;">
                    <span>Academic Change Requests</span>
                    <i class="fas fa-chevron-right nav-caret"></i>
                </a>
                <div class="nav-sub">
                    <?php foreach ($acrKeys as $file => $label): ?>
                        <?php $activeSub = $currentFolder === 'academic_change_requests' && $currentPage === $file; ?>
                        <a class="<?= $activeSub ? 'active' : '' ?>" href="/uni-mis-project/academic_change_requests/<?= $file; ?>">
                            <?= htmlspecialchars($label); ?>
                        </a>
                    <?php endforeach; ?>
            </div>
        </div>
        <script>
        function toggleNavGroup(id) {
            var el = document.getElementById(id);
            if (el) el.classList.toggle('open');
        }
        </script>

            <span class="nav-section-label">Faculty Management</span>
            <a class="<?= $currentFolder === 'faculty_registry' ? 'active' : '' ?>" href="/uni-mis-project/faculty_registry/index.php">
                Faculty Registry
            </a>
            <a class="<?= $currentFolder === 'faculty_management' ? 'active' : '' ?>" href="/uni-mis-project/faculty_management/index.php">
                Faculty Management
            </a>
            <a class="<?= $currentFolder === 'faculty_enquiry' ? 'active' : '' ?>" href="/uni-mis-project/faculty_enquiry/index.php">
                Faculty Enquiry
            </a>

            <span class="nav-section-label">Academics</span>
            <a class="<?= $currentFolder === 'Courses' ? 'active' : '' ?>" href="/uni-mis-project/Courses/index.php">
                Courses
            </a>
            <a class="<?= $currentFolder === 'attendance' ? 'active' : '' ?>" href="/uni-mis-project/attendance/index.php">
                Attendance
            </a>
            
            <a class="<?= $currentPage === 'lms_applications.php' ? 'active' : '' ?>" href="/uni-mis-project/lms_applications.php">
                Application
            </a>

            <?php
            $ttKeys = [
                'index.php'      => 'Timetable Home',
                'generate.php'   => 'Generate Timetable',
                'view.php'       => 'View Timetable',
                'conflicts.php'  => 'Conflict Detection',
                'adjust.php'     => 'Timetable Adjustment',
                'publish.php'    => 'Publish Timetable',
            ];
            $ttIsOpen = $currentFolder === 'timetable_management';
            ?>
            <div class="nav-group <?= $ttIsOpen ? 'open' : '' ?>" id="nav-tt">
                <a href="javascript:void(0);" class="nav-parent" onclick="toggleNavGroup('nav-tt'); return false;">
                    <span>Timetable Management</span>
                    <i class="fas fa-chevron-right nav-caret"></i>
                </a>
                <div class="nav-sub">
                    <?php foreach ($ttKeys as $file => $label): ?>
                        <?php $activeSub = $currentFolder === 'timetable_management' && $currentPage === $file; ?>
                        <a class="<?= $activeSub ? 'active' : '' ?>" href="/uni-mis-project/timetable_management/<?= $file; ?>">
                            <?= htmlspecialchars($label); ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>

            <?php
            $ssrKeys = [
                'index.php'    => 'Requests Home',
                'submit.php'   => 'Submit Request',
                'review.php'   => 'Review Requests',
                'history.php'  => 'Request History',
            ];
            $ssrIsOpen = $currentFolder === 'student_schedule_requests';
            ?>
            <div class="nav-group <?= $ssrIsOpen ? 'open' : '' ?>" id="nav-ssr">
                <a href="javascript:void(0);" class="nav-parent" onclick="toggleNavGroup('nav-ssr'); return false;">
                    <span>Student Schedule Requests</span>
                    <i class="fas fa-chevron-right nav-caret"></i>
                </a>
                <div class="nav-sub">
                    <?php foreach ($ssrKeys as $file => $label): ?>
                        <?php $activeSub = $currentFolder === 'student_schedule_requests' && $currentPage === $file; ?>
                        <a class="<?= $activeSub ? 'active' : '' ?>" href="/uni-mis-project/student_schedule_requests/<?= $file; ?>">
                            <?= htmlspecialchars($label); ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="spacer"></div>

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
