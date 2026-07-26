<?php
require_once 'config/db_connect.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);

    if (empty($email) || empty($password)) {
        $error = 'Please enter both email and password.';
    } else {
        $conn = getConnection();
        $stmt = $conn->prepare("SELECT u.*, r.role_name FROM users u JOIN roles r ON u.role_id = r.role_id WHERE u.email = ? LIMIT 1");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result && $result->num_rows > 0) {
            $user = $result->fetch_assoc();
            if ($user['is_active'] == 0) {
                $error = 'Your account is deactivated. Please contact the administrator.';
            } elseif ($password === $user['password_hash']) {
                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['full_name'] = $user['full_name'];
                $_SESSION['role_id'] = $user['role_id'];
                $_SESSION['role_name'] = $user['role_name'];

                header("Location: examination/dashboard.php");
                exit();
            } else {
                $error = 'Invalid email or password.';
            }
        } else {
            $error = 'Invalid email or password.';
        }
        $stmt->close();
        $conn->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - University MIS</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">
    <style>
        body {
            background-color: #f4f6f9;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .login-card {
            border: none;
            border-radius: 12px;
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.08);
            background: #ffffff;
        }
        .login-header {
            background: #ffffff;
            color: #0d6efd;
            border-top-left-radius: 12px;
            border-top-right-radius: 12px;
            padding: 30px 25px 15px;
            text-align: center;
            border-bottom: 1px solid #eef2f5;
        }
        .login-header i {
            font-size: 3.5rem;
            color: #0d6efd;
            margin-bottom: 10px;
            display: inline-block;
        }
        .btn-login {
            background: #0d6efd;
            color: white;
            border: none;
            padding: 12px;
            font-weight: 600;
            transition: all 0.2s ease-in-out;
        }
        .btn-login:hover {
            background: #0b5ed7;
            color: white;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(13, 110, 253, 0.2);
        }
        .form-control:focus {
            border-color: #86b7fe;
            box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
        }
        .input-group-text {
            background-color: #f8f9fa;
            border-right: none;
        }
        .form-control {
            border-left: none;
        }
        .credentials-box {
            background: #f0f7ff;
            border-radius: 8px;
            padding: 15px;
            margin-top: 25px;
            font-size: 0.85rem;
            border-left: 4px solid #0d6efd;
            color: #333;
        }
        .credentials-box code {
            color: #0d6efd;
            font-weight: 600;
        }
    </style>
</head>
<body>
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-5">
            <div class="card login-card">
                <div class="login-header">
                    <i class="bi bi-mortarboard-fill"></i>
                    <h4 class="mb-1 fw-bold">University MIS</h4>
                    <p class="text-muted mb-0 small">Examination & Management System</p>
                </div>
                <div class="card-body p-4">
                    <?php if (!empty($error)): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="bi bi-exclamation-triangle-fill me-2"></i>
                            <?php echo htmlspecialchars($error); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>

                    <form action="login.php" method="POST">
                        <div class="mb-3">
                            <label for="email" class="form-label">Email Address</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light"><i class="bi bi-envelope"></i></span>
                                <input type="email" class="form-control" id="email" name="email" placeholder="name@university.edu" required value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                            </div>
                        </div>
                        <div class="mb-4">
                            <label for="password" class="form-label">Password</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light"><i class="bi bi-lock"></i></span>
                                <input type="password" class="form-control" id="password" name="password" placeholder="••••••••" required>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-login w-100 rounded-3">
                            <i class="bi bi-box-arrow-in-right me-2"></i>Sign In
                        </button>
                    </form>

                    <div class="credentials-box">
                        <h6 class="fw-bold mb-2 text-primary"><i class="bi bi-info-circle-fill me-1"></i> Demo Accounts & Passwords:</h6>
                        <div class="row g-2">
                            <div class="col-6">
                                <div class="p-2 bg-white rounded border">
                                    <strong>Admin:</strong><br>
                                    <span class="text-muted">admin@university.edu</span><br>
                                    Password: <code>admin123</code>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="p-2 bg-white rounded border">
                                    <strong>Faculty:</strong><br>
                                    <span class="text-muted">faculty@university.edu</span><br>
                                    Password: <code>faculty123</code>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="p-2 bg-white rounded border">
                                    <strong>SSO:</strong><br>
                                    <span class="text-muted">sso@university.edu</span><br>
                                    Password: <code>sso123</code>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="p-2 bg-white rounded border">
                                    <strong>Student:</strong><br>
                                    <span class="text-muted">student1@university.edu</span><br>
                                    Password: <code>student123</code>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
