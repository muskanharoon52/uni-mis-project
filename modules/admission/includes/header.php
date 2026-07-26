<?php
// Check if user is logged in
if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
    header('Location: /uni-mis-project/modules/admission/auth/login.php');
    exit();
}

$page_title = $page_title ?? 'Dashboard';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admission System - <?= $page_title ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body { background: #f0f2f5; }
        .sidebar { min-height: 100vh; background: #1a2332; padding-top: 20px; position: fixed; width: 250px; }
        .sidebar .nav-link { color: #a8b2c9; padding: 12px 20px; border-radius: 8px; margin: 3px 10px; transition: all 0.3s; text-decoration: none; display: block; }
        .sidebar .nav-link:hover, .sidebar .nav-link.active { background: #2d3b4f; color: white; }
        .sidebar .nav-link i { margin-right: 10px; width: 20px; }
        .main-content { margin-left: 250px; padding: 20px; min-height: 100vh; }
        .top-bar { background: white; padding: 15px 25px; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); margin-bottom: 25px; }
        .stat-card { background: white; padding: 20px; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); transition: transform 0.3s; }
        .stat-card:hover { transform: translateY(-5px); }
        .stat-card .number { font-size: 28px; font-weight: 600; }
        .page-header { background: white; padding: 20px; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); margin-bottom: 20px; }
        .brand { color: white; padding: 0 20px 20px; border-bottom: 1px solid #2d3b4f; margin-bottom: 15px; }
        .brand h4 { color: white; font-weight: 600; }
        .brand small { color: #a8b2c9; }
        .user-info { color: #a8b2c9; padding: 15px 20px; border-top: 1px solid #2d3b4f; margin-top: 20px; position: absolute; bottom: 0; width: 100%; }
        .user-info a { color: #a8b2c9; text-decoration: none; }
        .user-info a:hover { color: white; }
        .card { background: white; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); padding: 20px; margin-bottom: 20px; }
        .table-responsive { overflow-x: auto; }
        .alert { margin-bottom: 20px; }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="brand">
            <h4><i class="fas fa-graduation-cap"></i> Admission</h4>
            <small>Management System</small>
        </div>
        <nav>
            <a class="nav-link <?= basename(dirname($_SERVER['PHP_SELF'])) == 'admission' ? 'active' : '' ?>" 
               href="/uni-mis-project/modules/admission/index.php">
                <i class="fas fa-home"></i> Dashboard
            </a>
            <a class="nav-link <?= basename(dirname($_SERVER['PHP_SELF'])) == 'applications' ? 'active' : '' ?>" 
               href="/uni-mis-project/modules/admission/applications/index.php">
                <i class="fas fa-file-alt"></i> Applications
            </a>
            <a class="nav-link <?= basename(dirname($_SERVER['PHP_SELF'])) == 'students' ? 'active' : '' ?>" 
               href="/uni-mis-project/modules/admission/students/index.php">
                <i class="fas fa-users"></i> Students
            </a>
            <a class="nav-link <?= basename(dirname($_SERVER['PHP_SELF'])) == 'fees' ? 'active' : '' ?>" 
               href="/uni-mis-project/modules/admission/fees/index.php">
                <i class="fas fa-money-bill"></i> Fees
            </a>
            <a class="nav-link <?= basename(dirname($_SERVER['PHP_SELF'])) == 'scholarships' ? 'active' : '' ?>" 
               href="/uni-mis-project/modules/admission/scholarships/index.php">
                <i class="fas fa-trophy"></i> Scholarships
            </a>
            <a class="nav-link <?= basename(dirname($_SERVER['PHP_SELF'])) == 'reports' ? 'active' : '' ?>" 
               href="/uni-mis-project/modules/admission/reports/index.php">
                <i class="fas fa-chart-bar"></i> Reports
            </a>
            <a class="nav-link" href="/uni-mis-project/modules/admission/settings/index.php">
                <i class="fas fa-cog"></i> Settings
            </a>
        </nav>
        <div class="user-info">
            <i class="fas fa-user-circle"></i> <?= $_SESSION['user_name'] ?? 'User' ?>
            <br><small><i class="fas fa-sign-out-alt"></i> <a href="/uni-mis-project/logout.php">Logout</a></small>
        </div>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <div class="top-bar d-flex justify-content-between align-items-center">
            <h5 class="mb-0"><?= $page_title ?></h5>
            <div>
                <span class="badge bg-primary"><?= $_SESSION['user_role'] ?? 'Staff' ?></span>
                <span class="ms-2 text-muted"><?= date('d M Y, h:i A') ?></span>
            </div>
        </div>