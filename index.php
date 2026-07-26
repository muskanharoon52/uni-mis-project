<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
session_start();
// Clear stale session to prevent auto-login
session_unset();
session_destroy();
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>University MIS - Home</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', system-ui, -apple-system, sans-serif; background: #f4f5f8; min-height: 100vh; }
        .navbar { background: #1a1d29; padding: 16px 32px; display: flex; align-items: center; justify-content: space-between; }
        .navbar-brand { color: #fff; font-size: 1.4rem; font-weight: 700; text-decoration: none; }
        .navbar-nav { display: flex; gap: 24px; list-style: none; }
        .navbar-nav a { color: #c9cdd4; text-decoration: none; font-size: 0.95rem; transition: color 0.2s; }
        .navbar-nav a:hover { color: #fff; }
        .container { max-width: 1100px; margin: 40px auto; padding: 0 24px; }
        h1 { font-size: 2rem; font-weight: 800; color: #1a1d29; margin-bottom: 8px; }
        .subtitle { color: #6b7280; font-size: 1rem; margin-bottom: 32px; }
        .module-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 24px; margin-bottom: 32px; }
        .module-card { background: #fff; border-radius: 14px; padding: 28px 24px; box-shadow: 0 1px 3px rgba(0,0,0,0.06); border: 1px solid #e5e7eb; transition: transform 0.15s, box-shadow 0.15s; }
        .module-card:hover { transform: translateY(-3px); box-shadow: 0 8px 24px rgba(0,0,0,0.08); }
        .module-card h2 { font-size: 1.2rem; font-weight: 700; color: #1a1d29; margin-bottom: 16px; display: flex; align-items: center; gap: 10px; }
        .module-card h2 .icon { width: 40px; height: 40px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 1.2rem; }
        .icon-lms { background: #ede9fe; color: #7c3aed; }
        .icon-sbe { background: #fef3c7; color: #d97706; }
        .icon-mis { background: #ecfdf5; color: #059669; }
        .sub-menu { list-style: none; padding: 0; }
        .sub-menu li { padding: 8px 0; border-bottom: 1px solid #f3f4f6; display: flex; justify-content: space-between; align-items: center; }
        .sub-menu li:last-child { border-bottom: none; }
        .sub-menu a { color: #4b5563; text-decoration: none; font-size: 0.9rem; }
        .sub-menu a:hover { color: #1a1d29; }
        .badge { font-size: 0.7rem; padding: 2px 8px; border-radius: 99px; font-weight: 600; }
        .badge-active { background: #dcfce7; color: #16a34a; }
        .badge-placeholder { background: #fef3c7; color: #d97706; }
        .badge-disabled { background: #fee2e2; color: #dc2626; cursor: not-allowed; }
    </style>
</head>
<body>
    <nav class="navbar">
        <a href="index.php" class="navbar-brand">University MIS</a>
        <ul class="navbar-nav">
            <li><a href="index.php">Home</a></li>
            <li><a href="modules/lms/public/login.php">LMS</a></li>
            <li><a href="modules/sbe/login.php">SBE</a></li>
            <li><a href="mis.php">MIS Portal</a></li>
            <li><a href="modules/examination/login.php">Examination</a></li>
            <li><a href="modules/sso/login.php">SSO Login</a></li>
            <li><a href="modules/finance/login.php">Finance</a></li>
        </ul>
    </nav>
    <div class="container">
        <h1>University Management Information System</h1>
        <p class="subtitle">Select a module to continue</p>
        <div class="module-grid">
            <div class="module-card">
                <h2><span class="icon icon-lms">&#128218;</span> LMS (Learning Management)</h2>
                <p style="color:#6b7280;font-size:0.9rem;margin-bottom:16px;">Course materials, assignments, grades, and student communications</p>
                <ul class="sub-menu">
                    <li>
                        <a href="modules/sbe/login.php">SBE (Admin Portal)</a>
                        <span class="badge badge-active">active</span>
                    </li>
                    <li>
                        <a href="modules/lms/public/login.php">Teacher</a>
                        <span class="badge badge-active">active</span>
                    </li>
                    <li>
                        <a href="modules/lms/public/login.php">Student</a>
                        <span class="badge badge-active">active</span>
                    </li>
                </ul>
            </div>
            <div class="module-card">
                <h2><span class="icon icon-mis">&#128221;</span> MIS (Management Info System)</h2>
                <p style="color:#6b7280;font-size:0.9rem;margin-bottom:16px;">Finance, admission, authentication, and examination management</p>
                <ul class="sub-menu">
                    <li>
                        <a href="modules/finance/login.php">Finance</a>
                        <span class="badge badge-active">active</span>
                    </li>
                    <li>
                        <span>Admission</span>
                        <span class="badge badge-disabled">Coming Soon</span>
                    </li>
                    <li>
                        <a href="modules/sso/login.php">SSO</a>
                        <span class="badge badge-active">active</span>
                    </li>
                    <li>
                        <a href="modules/examination/login.php">Examination</a>
                        <span class="badge badge-active">active</span>
                    </li>
                    <li>
                        <a href="mis.php">MIS Portal</a>
                        <span class="badge badge-active">active</span>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</body>
</html>