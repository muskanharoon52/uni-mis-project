<?php
session_start();

// Get module from URL parameter
$module = isset($_GET['module']) ? $_GET['module'] : 'mis';
$error = isset($_GET['error']) ? $_GET['error'] : '';

// Include database connection
include __DIR__ . '/../config/db_connect.php';

$login_error = '';
$already_logged_in = isset($_SESSION['user_id']);

// If user is already logged in and tries to login again, we still show the form
// but they can logout first or login with different credentials

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $password = $_POST['password'];
    $module = mysqli_real_escape_string($conn, $_POST['module']);

    // Query to find user
    $sql = "SELECT u.*, r.role_name FROM users u 
            JOIN roles r ON u.role_id = r.role_id 
            WHERE u.username = '$username' AND u.status = 'Active'";
    $result = mysqli_query($conn, $sql);

    if (mysqli_num_rows($result) == 1) {
        $user = mysqli_fetch_assoc($result);
        
        // Simple password check (plain text)
        if ($password == $user['password_hash']) {
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['full_name'] = $user['full_name'];
            $_SESSION['role_id'] = $user['role_id'];
            $_SESSION['role_name'] = $user['role_name'];
            $_SESSION['module'] = $module;

            // Update last login
            $update_sql = "UPDATE users SET last_login_at = NOW() WHERE user_id = '{$user['user_id']}'";
            mysqli_query($conn, $update_sql);

            // Redirect based on role AFTER successful login
            if ($user['role_id'] == 3 || $user['role_id'] == 1) {
                header('Location: ../finance/dashboard.php');
                exit();
            } else {
                header('Location: ../index.php');
                exit();
            }
        } else {
            $login_error = 'Invalid password!';
        }
    } else {
        $login_error = 'Username not found!';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - <?php echo ucfirst($module); ?> Module</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .login-container {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            max-width: 420px;
            width: 90%;
        }
        .login-container .logo {
            text-align: center;
            font-size: 3rem;
            color: #667eea;
            margin-bottom: 10px;
        }
        .login-container h3 {
            text-align: center;
            font-weight: 600;
            color: #2c3e50;
        }
        .login-container .module-badge {
            text-align: center;
            display: block;
            margin: 10px 0 20px;
        }
        .login-container .module-badge span {
            background: #667eea;
            color: #fff;
            padding: 5px 20px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
        }
        .login-container .back-link {
            text-align: center;
            margin-top: 15px;
        }
        .login-container .back-link a {
            color: #667eea;
            text-decoration: none;
            font-size: 0.9rem;
        }
        .login-container .back-link a:hover {
            text-decoration: underline;
        }
        .btn-login {
            background: #667eea;
            color: #fff;
            border: none;
            padding: 12px;
            font-weight: 600;
            width: 100%;
            border-radius: 8px;
            transition: 0.3s;
        }
        .btn-login:hover {
            background: #5a6fd6;
            color: #fff;
        }
        .btn-login:disabled {
            background: #bdc3c7;
            cursor: not-allowed;
        }
        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }
        .demo-credentials {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            margin-top: 15px;
            font-size: 0.85rem;
        }
        .demo-credentials small {
            color: #7f8c8d;
        }
        .already-logged-alert {
            background: #e8f0fe;
            border: 1px solid #667eea;
            color: #2c3e50;
            padding: 12px 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            text-align: center;
        }
        .already-logged-alert a {
            color: #667eea;
            font-weight: 600;
            text-decoration: none;
        }
        .already-logged-alert a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>

<div class="login-container">
    <div class="logo">
        <i class="fas fa-university"></i>
    </div>
    <h3>Login to <?php echo ucfirst($module); ?></h3>
    <div class="module-badge">
        <span><i class="fas fa-<?php echo $module == 'mis' ? 'building-columns' : 'graduation-cap'; ?>"></i> <?php echo strtoupper($module); ?> Module</span>
    </div>

    <?php if ($already_logged_in): ?>
        <div class="already-logged-alert">
            <i class="fas fa-info-circle"></i> You are already logged in as 
            <strong><?php echo htmlspecialchars($_SESSION['full_name'] ?? 'User'); ?></strong><br>
            <a href="../index.php">Go to Home</a> | 
            <a href="logout.php">Logout</a>
        </div>
    <?php endif; ?>

    <?php if (!empty($login_error)): ?>
        <div class="alert alert-danger"><?php echo $login_error; ?></div>
    <?php endif; ?>

    <?php if (!empty($error)): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <form method="POST" action="">
        <input type="hidden" name="module" value="<?php echo $module; ?>">
        
        <div class="mb-3">
            <label for="username" class="form-label">Username</label>
            <div class="input-group">
                <span class="input-group-text"><i class="fas fa-user"></i></span>
                <input type="text" class="form-control" id="username" name="username" 
                       placeholder="Enter your username" required autofocus>
            </div>
        </div>

        <div class="mb-3">
            <label for="password" class="form-label">Password</label>
            <div class="input-group">
                <span class="input-group-text"><i class="fas fa-lock"></i></span>
                <input type="password" class="form-control" id="password" name="password" 
                       placeholder="Enter your password" required>
            </div>
        </div>

        <button type="submit" class="btn-login">
            <i class="fas fa-sign-in-alt"></i> Login
        </button>

        <div class="back-link">
            <a href="../index.php"><i class="fas fa-arrow-left"></i> Back to Module Selection</a>
        </div>
    </form>

    <div class="demo-credentials">
        <small><i class="fas fa-info-circle"></i> Demo Credentials:</small><br>
        <small><strong>Finance:</strong> finance / finance123</small><br>
        <small><strong>Admin:</strong> admin / admin123</small>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>