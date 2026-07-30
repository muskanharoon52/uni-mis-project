<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include_once __DIR__ . '/../../../config/db_connect.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: /uni-mis-project/');
    exit();
}

if ($_SESSION['role_id'] != 3 && $_SESSION['role_id'] != 1) {
    header('Location: /uni-mis-project/');
    exit();
}

$pageTitle = $pageTitle ?? 'Finance';
$current_page = basename($_SERVER['PHP_SELF']);
$current_folder = basename(dirname($_SERVER['PHP_SELF']));
$userName = $_SESSION['full_name'] ?? 'User';
$userInitial = strtoupper(substr($userName, 0, 1));
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($pageTitle) ?> | Finance Module</title>
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
        <a href="/uni-mis-project/modules/finance/dashboard.php" class="brand">
            <div class="brand-mark">FIN</div>
            <div>
                <h1>Finance Module</h1>
                <p>University MIS</p>
            </div>
        </a>

        <nav class="nav">
            <span class="nav-section-label">Overview</span>
            <a class="<?= $current_page === 'dashboard.php' ? 'active' : '' ?>" href="/uni-mis-project/modules/finance/dashboard.php">
                <span class="nav-icon">&#128202;</span> Dashboard
            </a>

            <span class="nav-section-label">Fee Management</span>
            <a class="<?= $current_folder === 'fee_heads' ? 'active' : '' ?>" href="/uni-mis-project/modules/finance/fee_heads/index.php">
                <span class="nav-icon">&#127991;</span> Fee Heads
            </a>
            <a class="<?= $current_folder === 'fee_structure' ? 'active' : '' ?>" href="/uni-mis-project/modules/finance/fee_structure/index.php">
                <span class="nav-icon">&#128209;</span> Fee Structure
            </a>
            <a class="<?= $current_folder === 'student_fee' ? 'active' : '' ?>" href="/uni-mis-project/modules/finance/student_fee/index.php">
                <span class="nav-icon">&#127891;</span> Student Fee
            </a>

            <span class="nav-section-label">Payments</span>
            <a class="<?= $current_folder === 'payments' ? 'active' : '' ?>" href="/uni-mis-project/modules/finance/payments/index.php">
                <span class="nav-icon">&#128176;</span> Payments
            </a>
            <a class="<?= $current_folder === 'receipts' ? 'active' : '' ?>" href="/uni-mis-project/modules/finance/receipts/index.php">
                <span class="nav-icon">&#128196;</span> Receipts
            </a>

            <span class="nav-section-label">System</span>
            <a class="<?= $current_folder === 'logs' ? 'active' : '' ?>" href="/uni-mis-project/modules/finance/logs/index.php">
                <span class="nav-icon">&#128336;</span> Activity Logs
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
                <span class="eyebrow">Finance Module</span>
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
