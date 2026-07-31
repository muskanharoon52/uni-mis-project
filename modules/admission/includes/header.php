<?php
if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
    header('Location: /uni-mis-project/');
    exit();
}

$pageTitle = $pageTitle ?? $page_title ?? 'Dashboard';
$current_page = basename($_SERVER['PHP_SELF']);
$current_folder = basename(dirname($_SERVER['PHP_SELF']));
$userName = $_SESSION['full_name'] ?? $_SESSION['user_name'] ?? 'User';
$userInitial = strtoupper(substr($userName, 0, 1));
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($pageTitle) ?> | Admission System</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="/uni-mis-project/modules/lms/public/assets/style.css?v=<?= filemtime($_SERVER['DOCUMENT_ROOT'] . '/uni-mis-project/modules/lms/public/assets/style.css') ?>">
</head>
<body>
<button class="menu-toggle" id="menu-toggle">&#9776;</button>
<div class="sidebar-overlay" id="sidebar-overlay"></div>

<div class="app-shell">
    <aside class="sidebar" id="sidebar">
        <a href="/uni-mis-project/modules/admission/index.php" class="brand">
            <div class="brand-mark">ADM</div>
            <div>
                <h1>Admission System</h1>
                <p>University MIS</p>
            </div>
        </a>

        <nav class="nav">
            <span class="nav-section-label">Overview</span>
            <a class="<?= ($current_folder === 'admission' && $current_page === 'index.php') || ($current_folder === 'dashboard' && $current_page === 'index.php') ? 'active' : '' ?>" href="/uni-mis-project/modules/admission/index.php">
                Dashboard
            </a>

            <span class="nav-section-label">Admissions</span>
            <a class="<?= $current_folder === 'applications' ? 'active' : '' ?>" href="/uni-mis-project/modules/admission/applications/index.php">
                Applications
            </a>
            <a class="<?= $current_folder === 'enrollment' ? 'active' : '' ?>" href="/uni-mis-project/modules/admission/enrollment/index.php">
                Enrollment
            </a>
            <a class="<?= $current_folder === 'students' ? 'active' : '' ?>" href="/uni-mis-project/modules/admission/students/index.php">
                Students
            </a>

            <span class="nav-section-label">Finance & Awards</span>
            <a class="<?= $current_folder === 'scholarships' ? 'active' : '' ?>" href="/uni-mis-project/modules/admission/scholarships/index.php">
                Scholarships
            </a>

            <span class="nav-section-label">System</span>
            <a class="<?= $current_folder === 'reports' ? 'active' : '' ?>" href="/uni-mis-project/modules/admission/reports/index.php">
                Reports
            </a>
            <a class="<?= $current_folder === 'settings' ? 'active' : '' ?>" href="/uni-mis-project/modules/admission/settings/index.php">
                Settings
            </a>

            <div class="spacer"></div>

            <a href="/uni-mis-project/logout.php" class="sidebar-logout-btn">
                Logout
            </a>
        </nav>
    </aside>

    <main class="content">
        <div class="topbar">
            <div>
                <span class="eyebrow">Admission System</span>
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
                        <a href="/uni-mis-project/logout.php">&#x2190; Logout</a>
                    </div>
                </div>
            </div>
        </div>
