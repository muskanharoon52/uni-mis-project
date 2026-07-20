<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'includes/db.php';
require_once 'includes/auth.php';

// If already logged in, redirect to dashboard
if (isLoggedIn()) {
    header('Location: dashboard.php');
    exit();
}

$error = '';
$debug = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');
    
    if (!empty($email) && !empty($password)) {
        // Get user for debugging
        $sql = "SELECT u.*, r.role_name FROM users u 
                JOIN roles r ON u.role_id = r.role_id 
                WHERE u.email = ?";
        $user = getRow($sql, [$email]);
        
        if ($user) {
            $debug .= "User found: " . $user['email'] . "<br>";
            $debug .= "Role: " . $user['role_name'] . "<br>";
            $debug .= "Active: " . ($user['is_active'] ? 'Yes' : 'No') . "<br>";
            $debug .= "Plain password in DB: " . $user['plain_password'] . "<br>";
            $debug .= "Password entered: " . $password . "<br>";
            
            // Check password match
            if ($user['plain_password'] == $password) {
                $debug .= "✅ Password MATCHES plain_password!<br>";
            } else {
                $debug .= "❌ Password does NOT match plain_password<br>";
            }
            
            // Try login
            if (loginUser($email, $password)) {
                $debug .= "✅ Login successful!<br>";
                if (isSSO() || isAdmin()) {
                    header('Location: dashboard.php');
                    exit();
                } else {
                    logoutUser();
                    $error = 'You do not have permission to access SSO panel.';
                }
            } else {
                $debug .= "❌ Login function returned false<br>";
                $error = 'Invalid email or password.';
            }
        } else {
            $debug .= "❌ User NOT found in database<br>";
            $error = 'Invalid email or password.';
        }
    } else {
        $error = 'Please enter email and password.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SSO Login - University MIS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
        }
        .login-card {
            max-width: 450px;
            margin: auto;
            border-radius: 15px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.3);
        }
        .login-card .card-header {
            background: #2d3748;
            color: white;
            border-radius: 15px 15px 0 0;
            padding: 20px;
            text-align: center;
        }
        .login-card .card-body {
            padding: 30px;
        }
        .debug-box {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 5px;
            padding: 10px;
            margin-top: 15px;
            font-size: 13px;
            font-family: monospace;
            max-height: 200px;
            overflow: auto;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row">
            <div class="col-md-12">
                <div class="card login-card">
                    <div class="card-header">
                        <h3><i class="fas fa-university"></i> SSO Portal</h3>
                        <p class="mb-0">Student Services Office</p>
                    </div>
                    <div class="card-body">
                        <?php if ($error): ?>
                            <div class="alert alert-danger">
                                <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
                            </div>
                        <?php endif; ?>
                        
                        <form method="POST">
                            <div class="mb-3">
                                <label for="email" class="form-label">Email Address</label>
                                <input type="email" class="form-control" id="email" name="email" 
                                       placeholder="Enter your email" 
                                       value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>"
                                       required>
                            </div>
                            <div class="mb-3">
                                <label for="password" class="form-label">Password</label>
                                <input type="password" class="form-control" id="password" name="password" 
                                       placeholder="Enter your password" required>
                            </div>
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="fas fa-sign-in-alt"></i> Login
                            </button>
                        </form>
                        
                        <?php if (!empty($debug)): ?>
                            <div class="debug-box">
                                <strong>🔍 Debug Info:</strong><br>
                                <?php echo $debug; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="card-footer text-center">
                        <small class="text-muted">
                            <i class="fas fa-info-circle"></i> Demo: sso@university.edu / password123
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>