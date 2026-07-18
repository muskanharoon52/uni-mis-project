<?php
session_start();
include $_SERVER['DOCUMENT_ROOT'] . '/MIS/config/db_connect.php';

$error = '';

// // If already logged in, redirect to dashboard
// if (isset($_SESSION['user_id'])) {
//     header('Location: ../finance/dashboard.php');
//     exit();
// }

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $password = mysqli_real_escape_string($conn, $_POST['password']);

    // Query to check user
    $sql = "SELECT user_id, full_name, username, password_hash, role_id 
            FROM users 
            WHERE username = '$username' AND status = 'Active'";
    $result = mysqli_query($conn, $sql);

    if (mysqli_num_rows($result) == 1) {
        $user = mysqli_fetch_assoc($result);
        
        // Verify password (plain text for now)
        if ($password === $user['password_hash']) {
            // Set session
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['full_name'] = $user['full_name'];
            $_SESSION['role_id'] = $user['role_id'];
            
            // Update last login
            $update_sql = "UPDATE users SET last_login_at = NOW() WHERE user_id = '{$user['user_id']}'";
            mysqli_query($conn, $update_sql);
            
            // Redirect to finance dashboard
            header('Location: ../finance/dashboard.php');
            exit();
        } else {
            $error = "Invalid password!";
        }
    } else {
        $error = "User not found!";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Finance Module</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #2c3e50, #3498db);
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .login-card {
            background: #fff;
            border-radius: 15px;
            padding: 40px;
            max-width: 400px;
            width: 100%;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
        }
        .login-card .brand {
            text-align: center;
            margin-bottom: 30px;
        }
        .login-card .brand h2 {
            color: #2c3e50;
            font-weight: bold;
        }
        .login-card .brand small {
            color: #7f8c8d;
        }
        .login-card .form-control {
            border-radius: 10px;
            padding: 12px 15px;
        }
        .login-card .btn-login {
            background: #2c3e50;
            color: #fff;
            border: none;
            border-radius: 10px;
            padding: 12px;
            font-weight: bold;
            width: 100%;
            transition: 0.3s;
        }
        .login-card .btn-login:hover {
            background: #34495e;
            transform: scale(1.02);
        }
    </style>
</head>
<body>

<div class="login-card">
    <div class="brand">
        <h2><i class="fas fa-university text-primary"></i> Finance</h2>
        <small>University MIS - Finance Module</small>
    </div>

    <?php if(!empty($error)): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <form method="POST" action="">
        <div class="mb-3">
            <label class="form-label"><i class="fas fa-user"></i> Username</label>
            <input type="text" class="form-control" name="username" placeholder="Enter username" required>
        </div>
        <div class="mb-3">
            <label class="form-label"><i class="fas fa-lock"></i> Password</label>
            <input type="password" class="form-control" name="password" placeholder="Enter password" required>
        </div>
        <button type="submit" class="btn-login">
            <i class="fas fa-sign-in-alt"></i> Login
        </button>
    </form>

    <div class="mt-3 text-center text-muted">
        <small>Finance Officer Login</small>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>