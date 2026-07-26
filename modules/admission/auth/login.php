<?php
require_once __DIR__ . '/../config/database.php';

if (isLoggedIn()) {
    header('Location: ' . BASE_URL . 'dashboard/');
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitize($_POST['username']);
    $password = $_POST['password'];
    
    if (empty($username) || empty($password)) {
        $error = 'Please enter username and password';
    } else {
        // Check in users table - using your column names
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$username, $username]);
        $user = $stmt->fetch();
        
        if ($user) {
            // Your table uses 'password_hash' instead of 'password'
            $stored_password = $user['password_hash'] ?? $user['password'] ?? '';
            
            // Check if password matches (plain text or hashed)
            if ($password === $stored_password || password_verify($password, $stored_password)) {
                $_SESSION['user_id'] = $user['user_id'] ?? $user['id'];
                $_SESSION['user_name'] = $user['full_name'];
                $_SESSION['user_role'] = $user['role_id'] ?? 'staff';
                $_SESSION['user_email'] = $user['email'];
                
                header('Location: ' . BASE_URL . 'dashboard/');
                exit();
            } else {
                $error = 'Invalid username or password';
            }
        } else {
            $error = 'Invalid username or password';
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Login - Admission System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; display: flex; align-items: center; justify-content: center; margin: 0;">
    <div class="card p-4 shadow-lg" style="width: 400px; border-radius: 15px;">
        <div class="text-center mb-4">
            <i class="fas fa-university fa-3x text-primary"></i>
            <h4 class="mt-2">Admission System</h4>
            <small class="text-muted">University Management</small>
        </div>
        
        <?php if ($error): ?>
            <div class="alert alert-danger"><?= $error ?></div>
        <?php endif; ?>
        
        <form method="POST">
            <div class="mb-3">
                <label class="form-label">Username or Email</label>
                <input type="text" name="username" class="form-control" required autofocus value="finance">
            </div>
            <div class="mb-3">
                <label class="form-label">Password</label>
                <input type="password" name="password" class="form-control" required value="123456">
            </div>
            <button type="submit" class="btn btn-primary w-100">
                <i class="fas fa-sign-in-alt"></i> Login
            </button>
        </form>
        <div class="mt-3 text-center text-muted small">
            Demo: <strong>finance</strong> / <strong>123456</strong>
        </div>
    </div>
</body>
</html>