<?php
// Start session to check if user is logged in
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>University MIS - Select Module</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .landing-container {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 20px;
            padding: 50px 40px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            max-width: 800px;
            width: 90%;
            text-align: center;
        }
        .landing-container .logo {
            font-size: 4rem;
            color: #667eea;
            margin-bottom: 15px;
        }
        .landing-container h1 {
            font-size: 2.5rem;
            font-weight: 700;
            color: #2c3e50;
            margin-bottom: 10px;
        }
        .landing-container .subtitle {
            color: #7f8c8d;
            font-size: 1.1rem;
            margin-bottom: 40px;
        }
        .module-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 25px;
            margin-top: 20px;
        }
        .module-card {
            background: #f8f9fa;
            border-radius: 15px;
            padding: 30px 20px;
            text-decoration: none;
            color: #2c3e50;
            transition: all 0.3s ease;
            border: 2px solid transparent;
            cursor: pointer;
        }
        .module-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 40px rgba(0,0,0,0.15);
            border-color: #667eea;
            background: #fff;
        }
        .module-card .icon {
            font-size: 3.5rem;
            margin-bottom: 15px;
        }
        .module-card .icon.mis { color: #667eea; }
        .module-card .icon.lms { color: #f093fb; }
        .module-card h3 {
            font-size: 1.4rem;
            font-weight: 600;
            margin-bottom: 10px;
        }
        .module-card p {
            color: #7f8c8d;
            font-size: 0.95rem;
            margin-bottom: 0;
        }
        .module-card .badge-module {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
            margin-top: 10px;
        }
        .badge-mis { background: #667eea; color: #fff; }
        .badge-lms { background: #f093fb; color: #fff; }
        .footer-text {
            margin-top: 30px;
            color: #bdc3c7;
            font-size: 0.85rem;
        }
        .already-logged {
            margin-top: 20px;
            padding: 10px;
            background: #e8f0fe;
            border-radius: 10px;
            color: #667eea;
            font-size: 0.9rem;
        }
        .already-logged a {
            color: #667eea;
            font-weight: 600;
            text-decoration: none;
        }
        .already-logged a:hover {
            text-decoration: underline;
        }
        @media (max-width: 600px) {
            .landing-container { padding: 30px 20px; }
            .landing-container h1 { font-size: 1.8rem; }
            .module-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>

<div class="landing-container">
    <div class="logo">
        <i class="fas fa-university"></i>
    </div>
    <h1>University Management System</h1>
    <p class="subtitle">Select the module you want to access</p>

    <?php if (isset($_SESSION['user_id'])): ?>
        <div class="already-logged">
            <i class="fas fa-info-circle"></i> You are currently logged in as 
            <strong><?php echo htmlspecialchars($_SESSION['full_name'] ?? 'User'); ?></strong>
            (<a href="auth/logout.php">Logout</a>) | 
            <a href="finance/dashboard.php">Go to Dashboard</a>
        </div>
    <?php endif; ?>

    <div class="module-grid">
        <!-- MIS Module Card -->
        <a href="auth/login.php?module=mis" class="module-card">
            <div class="icon mis">
                <i class="fas fa-building-columns"></i>
            </div>
            <h3>MIS</h3>
            <p>Management Information System for administrative tasks</p>
            <span class="badge-module badge-mis">Finance • Admission • SSO</span>
        </a>

        <!-- LMS Module Card -->
        <a href="auth/login.php?module=lms" class="module-card">
            <div class="icon lms">
                <i class="fas fa-graduation-cap"></i>
            </div>
            <h3>LMS</h3>
            <p>Learning Management System for students & teachers</p>
            <span class="badge-module badge-lms">Quizzes • Exams • Attendance</span>
        </a>
    </div>

    <div class="footer-text">
        <p>&copy; <?php echo date('Y'); ?> University Management System. All rights reserved.</p>
    </div>
</div>

</body>
</html>