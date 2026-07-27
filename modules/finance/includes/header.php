<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include database connection - FIXED: 3 levels up (not 2)
include_once __DIR__ . '/../../../config/db_connect.php';

// Check if user is logged in via SSO
if (!isset($_SESSION['user_id'])) {
    header('Location: /uni-mis-project/modules/sso/login.php?error=Please login first');
    exit();
}

// Check if user is Finance Officer (role_id = 3) or Admin (role_id = 1)
if ($_SESSION['role_id'] != 3 && $_SESSION['role_id'] != 1) {
    header('Location: /uni-mis-project/modules/sso/login.php?error=Access denied');
    exit();
}

// Get current page name for active link
$current_page = basename($_SERVER['PHP_SELF']);
$current_folder = basename(dirname($_SERVER['PHP_SELF']));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Finance Module - University MIS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background: #f4f6f9; font-family: 'Segoe UI', sans-serif; }
        .sidebar {
            height: 100vh;
            background: #2c3e50;
            color: #fff;
            padding: 20px 0;
            position: fixed;
            width: 250px;
            overflow-y: auto;
            z-index: 1000;
        }
        .sidebar .brand {
            text-align: center;
            padding: 20px 0;
            border-bottom: 1px solid #34495e;
        }
        .sidebar .brand h4 { font-weight: bold; color: #ecf0f1; }
        .sidebar .brand small { color: #f1c40f !important; font-weight: bold; }
        .sidebar .nav-link {
            color: #bdc3c7;
            padding: 12px 20px;
            display: flex;
            align-items: center;
            gap: 12px;
            text-decoration: none;
            transition: 0.3s;
        }
        .sidebar .nav-link:hover, .sidebar .nav-link.active {
            background: #34495e;
            color: #fff;
        }
        .sidebar .nav-link i { width: 20px; text-align: center; }
        .sidebar .nav-link .arrow { margin-left: auto; transition: 0.3s; }
        .sidebar .nav-link .arrow.open { transform: rotate(90deg); }
        .sidebar .sub-menu {
            list-style: none;
            padding-left: 30px;
            display: none;
        }
        .sidebar .sub-menu.show { display: block; }
        .sidebar .sub-menu .nav-link { padding: 8px 20px; font-size: 0.85rem; }
        .sidebar .logout-btn {
            background: #e74c3c;
            color: #fff;
            padding: 10px 20px;
            border-radius: 5px;
            margin: 20px 15px;
            display: block;
            text-align: center;
            text-decoration: none;
        }
        .sidebar .logout-btn:hover { background: #c0392b; color: #fff; }
        .main-content {
            margin-left: 250px;
            padding: 20px;
        }
        .user-badge {
            background: #34495e;
            color: #fff;
            padding: 8px 15px;
            border-radius: 20px;
            font-size: 0.9rem;
        }
        @media (max-width: 768px) {
            .sidebar { width: 100%; height: auto; position: relative; }
            .main-content { margin-left: 0; }
        }
    </style>
    <script>
        function toggleSubMenu(event) {
            event.preventDefault();
            const subMenu = event.currentTarget.nextElementSibling;
            const arrow = event.currentTarget.querySelector('.arrow');
            if (subMenu) {
                subMenu.classList.toggle('show');
                if (arrow) {
                    arrow.classList.toggle('open');
                }
            }
        }
    </script>
</head>
<body>

<!-- ===== SIDEBAR ===== -->
<div class="sidebar">
    <div class="brand">
        <h4><i class="fas fa-university"></i> Finance</h4>
        <small>University MIS</small>
    </div>
    <ul class="nav flex-column mt-3">
        <!-- Dashboard -->
        <li class="nav-item">
            <a class="nav-link <?php echo ($current_page == 'dashboard.php') ? 'active' : ''; ?>" href="/uni-mis-project/modules/finance/dashboard.php">
                <i class="fas fa-chart-pie"></i> Dashboard
            </a>
        </li>
        
        <!-- Fee Heads -->
        <li class="nav-item">
            <a class="nav-link <?php echo ($current_folder == 'fee_heads') ? 'active' : ''; ?>" href="/uni-mis-project/modules/finance/fee_heads/index.php">
                <i class="fas fa-tags"></i> Fee Heads
            </a>
        </li>
        
        <!-- Fee Structure -->
        <li class="nav-item">
            <a class="nav-link <?php echo ($current_folder == 'fee_structure') ? 'active' : ''; ?>" href="/uni-mis-project/modules/finance/fee_structure/index.php">
                <i class="fas fa-layer-group"></i> Fee Structure
            </a>
        </li>
        
        <!-- Student Fee with Submenu -->
        <li class="nav-item">
            <a class="nav-link <?php echo ($current_folder == 'student_fee') ? 'active' : ''; ?>" href="#" onclick="toggleSubMenu(event)">
                <i class="fas fa-user-graduate"></i> Student Fee
                <span class="arrow <?php echo ($current_folder == 'student_fee') ? 'open' : ''; ?>"><i class="fas fa-chevron-right"></i></span>
            </a>
            <ul class="sub-menu <?php echo ($current_folder == 'student_fee') ? 'show' : ''; ?>" id="studentFeeSubMenu">
                <li><a class="nav-link" href="/uni-mis-project/modules/finance/student_fee/generate.php"><i class="fas fa-plus-circle text-success"></i> Generate Fee</a></li>
                <li><a class="nav-link" href="/uni-mis-project/modules/finance/student_fee/index.php"><i class="fas fa-list text-primary"></i> Fee Records</a></li>
                <li><a class="nav-link" href="/uni-mis-project/modules/finance/student_fee/view.php"><i class="fas fa-eye text-info"></i> View Fee</a></li>
            </ul>
        </li>
        
        <!-- Payments -->
        <li class="nav-item">
            <a class="nav-link <?php echo ($current_folder == 'payments') ? 'active' : ''; ?>" href="/uni-mis-project/modules/finance/payments/index.php">
                <i class="fas fa-money-bill-wave"></i> Payments
            </a>
        </li>
        
        <!-- Receipts -->
        <li class="nav-item">
            <a class="nav-link <?php echo ($current_folder == 'receipts') ? 'active' : ''; ?>" href="/uni-mis-project/modules/finance/receipts/index.php">
                <i class="fas fa-receipt"></i> Receipts
            </a>
        </li>
        
        <!-- Activity Logs -->
        <li class="nav-item">
            <a class="nav-link <?php echo ($current_folder == 'logs') ? 'active' : ''; ?>" href="/uni-mis-project/modules/finance/logs/index.php">
                <i class="fas fa-history"></i> Activity Logs
            </a>
        </li>
    </ul>
    
    <!-- Logout -->
    <a href="/uni-mis-project/logout.php" class="logout-btn">
    <i class="fas fa-sign-out-alt"></i> Logout
</a>
</div>

<!-- ===== MAIN CONTENT START ===== -->
<div class="main-content" id="mainContent">