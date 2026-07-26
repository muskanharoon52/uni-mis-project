<?php
// login.php

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    
    if (loginUser($email, $password)) {
        header('Location: dashboard.php');
        exit;
    } else {
        $error = 'Invalid email or password!';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - University MIS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .login-card {
            background: white;
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            width: 100%;
            max-width: 420px;
        }
        .login-card h2 { color: #333; font-weight: 700; }
        .login-card .subtitle { color: #666; font-size: 14px; margin-bottom: 30px; }
        .form-control { border-radius: 10px; padding: 12px 15px; }
        .btn-login {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            padding: 12px;
            border-radius: 10px;
            font-weight: 600;
            color: white;
            width: 100%;
        }
        .btn-login:hover { transform: translateY(-2px); box-shadow: 0 5px 20px rgba(102, 126, 234, 0.4); }
        .logo-icon { font-size: 48px; color: #667eea; margin-bottom: 15px; }
        .credentials-box {
            background: #f8f9fa;
            padding: 10px;
            border-radius: 8px;
            font-size: 13px;
        }
    </style>
</head>
<body>
    <div class="login-card">
        <div class="text-center">
            <i class="fas fa-university logo-icon"></i>
            <h2>University MIS</h2>
            <p class="subtitle">Student Services Office Module</p>
        </div>
        
        <?php if ($error): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <form method="POST">
            <div class="mb-3">
                <label class="form-label fw-semibold">Email Address</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                    <input type="email" name="email" class="form-control" 
                           placeholder="sso@university.edu" required>
                </div>
            </div>
            
            <div class="mb-3">
                <label class="form-label fw-semibold">Password</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="fas fa-lock"></i></span>
                    <input type="password" name="password" class="form-control" 
                           placeholder="Enter password" required>
                </div>
            </div>
            
            <button type="submit" class="btn btn-login">
                <i class="fas fa-sign-in-alt me-2"></i> Login
            </button>
        </form>
        
        <hr>
        <div class="credentials-box">
            <p class="mb-1"><strong>SSO Credentials:</strong></p>
            <p class="mb-0">Email: <strong>sso@university.edu</strong></p>
            <p class="mb-0">Password: <strong>password123</strong></p>
        </div>
    </div>
</body>
</html>