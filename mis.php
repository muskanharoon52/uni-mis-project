<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <title>MIS Portal Selection - University MIS</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', system-ui, -apple-system, sans-serif; background: #F8FAFC; min-height: 100vh; }
        .navbar { background: #162B4D; padding: 16px 32px; display: flex; align-items: center; justify-content: space-between; }
        .navbar-brand { color: #fff; font-size: 1.4rem; font-weight: 700; text-decoration: none; }
        .navbar-nav { display: flex; gap: 24px; list-style: none; }
        .navbar-nav a { color: #c9cdd4; text-decoration: none; font-size: 0.95rem; transition: color 0.2s; }
        .navbar-nav a:hover { color: #fff; }
        .container { max-width: 1100px; margin: 40px auto; padding: 0 24px; }
        h1 { font-size: 2rem; font-weight: 800; color: #1a1d29; margin-bottom: 8px; }
        .subtitle { color: #6b7280; font-size: 1rem; margin-bottom: 32px; }
        .module-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 24px; }
        .module-card { background: #fff; border-radius: 14px; padding: 28px 24px; box-shadow: 0 1px 3px rgba(0,0,0,0.06); border: 1px solid #e5e7eb; transition: transform 0.15s, box-shadow 0.15s; text-decoration: none; color: inherit; display: block; }
        .module-card:hover { transform: translateY(-3px); box-shadow: 0 8px 24px rgba(0,0,0,0.08); }
        .module-card.disabled { opacity: 0.5; pointer-events: none; }
        .module-card h2 { font-size: 1.2rem; font-weight: 700; color: #1a1d29; margin-bottom: 10px; display: flex; align-items: center; gap: 10px; }
        .module-card h2 .icon { width: 40px; height: 40px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 1.2rem; }
        .icon-sso { background: #ede9fe; color: #7c3aed; }
        .icon-exam { background: #ecfdf5; color: #059669; }
        .icon-finance { background: #fef3c7; color: #d97706; }
        .module-card p { color: #6b7280; font-size: 0.9rem; line-height: 1.5; }
        .badge { font-size: 0.7rem; padding: 2px 8px; border-radius: 99px; font-weight: 600; display: inline-block; margin-top: 8px; }
        .badge-active { background: #dcfce7; color: #16a34a; }
        .badge-disabled { background: #fee2e2; color: #dc2626; }
    </style>
</head>
<body>
    <nav class="navbar">
        <a href="index.php" class="navbar-brand">University MIS</a>
        <ul class="navbar-nav">
            <li><a href="index.php">Home</a></li>
            <li><a href="examination/index.php">Examination</a></li>
            <li><a href="/uni-mis-project/">SSO</a></li>
            <li><a href="/uni-mis-project/">Finance</a></li>
        </ul>
    </nav>
    <div class="container">
        <h1>Management Information System</h1>
        <p class="subtitle">Select a module to access</p>
        <div class="module-grid">
            <a href="/uni-mis-project/" class="module-card">
                <h2><span class="icon icon-sso">&#128274;</span> SSO Authentication</h2>
                <p>Single Sign-On login for all modules. Manage user roles and authentication.</p>
                <span class="badge badge-active">Active</span>
            </a>
            <a href="examination/index.php" class="module-card">
                <h2><span class="icon icon-exam">&#128202;</span> Examination</h2>
                <p>Exam scheduling, result management, grading, and semester promotions.</p>
                <span class="badge badge-active">Active</span>
            </a>
            <a href="/uni-mis-project/" class="module-card">
                <h2><span class="icon icon-finance">&#128176;</span> Finance</h2>
                <p>Fee management, payment tracking, receipts, scholarship handling.</p>
                <span class="badge badge-active">Active</span>
            </a>
            <div class="module-card disabled">
                <h2><span class="icon" style="background:#fee2e2;color:#dc2626;">&#128203;</span> Admission</h2>
                <p>Student admission applications and enrollment management.</p>
                <span class="badge badge-disabled">Coming Soon</span>
            </div>
        </div>
    </div>
</body>
</html>