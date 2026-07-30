<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../config/db_connect.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$pageTitle = $pageTitle ?? 'Examination';
$currentPage = basename($_SERVER['PHP_SELF']);
$currentDir = basename(dirname($_SERVER['PHP_SELF']));
$userName = $_SESSION['full_name'] ?? 'User';
$userInitial = strtoupper(substr($userName, 0, 1));

function getGradeColor($grade) {
    switch ($grade) {
        case 'A': return 'bg-success';
        case 'B': return 'bg-primary';
        case 'C': return 'bg-warning';
        case 'D': return 'bg-info';
        case 'F': return 'bg-danger';
        default: return 'bg-secondary';
    }
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($pageTitle) ?> | Examination Module</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="<?= BASE_URL ?>modules/lms/public/assets/style.css?v=<?= filemtime($_SERVER['DOCUMENT_ROOT'] . '/uni-mis-project/modules/lms/public/assets/style.css') ?>">
    <style>
        .content-area { padding: 0; margin: 0; }
        .badge-grade-A { background: var(--success-bg); color: #065f46; border: 1px solid var(--success-border); }
        .badge-grade-B { background: var(--info-bg); color: #1e40af; border: 1px solid var(--info-border); }
        .badge-grade-C { background: var(--warning-bg); color: #92400e; border: 1px solid var(--warning-border); }
        .badge-grade-D { background: #F0FDFA; color: #115E59; border: 1px solid #99F6E4; }
        .badge-grade-F { background: var(--danger-bg); color: #991b1b; border: 1px solid var(--danger-border); }
        .badge-exam-mid { background: var(--warning-bg); color: #92400e; border: 1px solid var(--warning-border); }
        .badge-exam-final { background: var(--danger-bg); color: #991b1b; border: 1px solid var(--danger-border); }
        .badge-exam-quiz { background: var(--info-bg); color: #1e40af; border: 1px solid var(--info-border); }
        .badge-exam-lab { background: #F3E8FF; color: #7c3aed; border: 1px solid #DDD6FE; }
        .stat-card { border: 1px solid var(--border); border-radius: var(--radius); background: var(--panel); }
        .stat-card .stat-icon { font-size: 2rem; margin-bottom: 10px; }
        .stat-card .stat-number { font-size: 1.5rem; font-weight: 800; color: var(--text-strong); }
        .stat-card .stat-label { color: var(--text-secondary); font-size: 14px; }
        .stat-card-primary .stat-icon { color: var(--accent); }
        .stat-card-success .stat-icon { color: var(--success); }
        .stat-card-warning .stat-icon { color: var(--warning); }
        .stat-card-info .stat-icon { color: #0891b2; }
        .card { border: 1px solid var(--border); border-radius: var(--radius); background: var(--panel); box-shadow: none; }
        .card-header { background: var(--bg); border-bottom: 1px solid var(--border); }
        .card-header h5 { margin: 0; font-size: 1rem; font-weight: 700; color: var(--text-strong); }
        .timeline-item { padding: 15px 0; border-bottom: 1px solid var(--border); }
        .timeline-item:last-child { border-bottom: none; }
        .timeline-item .time { font-size: 12px; color: var(--muted); margin-bottom: 5px; }
        .timeline-item .title { font-weight: 600; color: var(--text-strong); }
        .timeline-item .description { font-size: 13px; color: var(--text-secondary); margin-top: 5px; }
    </style>
</head>
<body>
<button class="menu-toggle" id="menu-toggle">&#9776;</button>
<div class="sidebar-overlay" id="sidebar-overlay"></div>

<div class="app-shell">
    <aside class="sidebar" id="sidebar">
        <a href="<?= BASE_URL ?>examination/dashboard.php" class="brand">
            <div class="brand-mark">EXM</div>
            <div>
                <h1>Examination</h1>
                <p>University MIS</p>
            </div>
        </a>

        <nav class="nav">
            <span class="nav-section-label">Overview</span>
            <a class="<?= $currentPage === 'dashboard.php' ? 'active' : '' ?>" href="<?= BASE_URL ?>examination/dashboard.php">
                <span class="nav-icon">&#127968;</span> Dashboard
            </a>

            <span class="nav-section-label">Scheduling</span>
            <a class="<?= $currentDir === 'schedule' && $currentPage === 'index.php' ? 'active' : '' ?>" href="<?= BASE_URL ?>examination/schedule/index.php">
                <span class="nav-icon">&#128197;</span> Exam Schedule
            </a>
            <a class="<?= $currentDir === 'schedule' && $currentPage === 'add.php' ? 'active' : '' ?>" href="<?= BASE_URL ?>examination/schedule/add.php">
                <span class="nav-icon">&#10133;</span> Add Schedule
            </a>

            <span class="nav-section-label">Results</span>
            <a class="<?= $currentDir === 'results' && $currentPage === 'index.php' ? 'active' : '' ?>" href="<?= BASE_URL ?>examination/results/index.php">
                <span class="nav-icon">&#128202;</span> Results
            </a>
            <a class="<?= $currentDir === 'results' && $currentPage === 'add.php' ? 'active' : '' ?>" href="<?= BASE_URL ?>examination/results/add.php">
                <span class="nav-icon">&#10133;</span> Add Result
            </a>
            <a class="<?= $currentDir === 'results' && $currentPage === 'publish.php' ? 'active' : '' ?>" href="<?= BASE_URL ?>examination/results/publish.php">
                <span class="nav-icon">&#9729;</span> Publish Results
            </a>
            <a class="<?= $currentDir === 'sbe-results' ? 'active' : '' ?>" href="<?= BASE_URL ?>examination/sbe-results/index.php">
                <span class="nav-icon">&#128221;</span> SBE Results
            </a>

            <span class="nav-section-label">Promotion</span>
            <a class="<?= $currentDir === 'promote' ? 'active' : '' ?>" href="<?= BASE_URL ?>examination/promote/promote.php">
                <span class="nav-icon">&#11014;</span> Promote Students
            </a>

            <div class="spacer"></div>

            <a href="<?= BASE_URL ?>modules/sso/logout.php" class="sidebar-logout-btn">
                Logout
            </a>
        </nav>
    </aside>

    <main class="content">
        <div class="topbar">
            <div>
                <span class="eyebrow">Examination Module</span>
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
                        <a href="<?= BASE_URL ?>modules/sso/logout.php">&#x2190; Logout</a>
                    </div>
                </div>
            </div>
        </div>
