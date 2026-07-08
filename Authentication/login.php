<?php
// Authentication/login.php

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';

// Redirect if already logged in
if (isLoggedIn()) {
    header("Location: ../Dashboard.php");
    exit;
}

$error = '';
$conn = getConnection();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $username = sanitize($_POST['username']);
    $password = $_POST['password'];

    if (empty($username) || empty($password)) {

        $error = "Username and password are required!";

    } else {

        $user = getSingleRecord("
            SELECT u.*, r.role_name
            FROM users u
            INNER JOIN roles r ON u.role_id = r.role_id
            WHERE u.username = ? AND u.status = 'Active'
        ", [$username], "s");

        if ($user) {

            // Plain text password (Demo)
            if ($password == $user['password_hash']) {

                loginUser(
                    $user['user_id'],
                    $user['username'],
                    $user['role_name'],
                    $user['full_name']
                );

                header("Location: ../Dashboard.php");
                exit;

            } else {

                $error = "Invalid Password!";

            }

        } else {

            $error = "User not found or inactive!";

        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">

<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>University MIS Login</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<style>

body{
    background:linear-gradient(135deg,#667eea,#764ba2);
    height:100vh;
    display:flex;
    justify-content:center;
    align-items:center;
}

.login-card{
    width:420px;
    background:#fff;
    padding:35px;
    border-radius:15px;
    box-shadow:0 20px 50px rgba(0,0,0,.25);
}

.logo{
    text-align:center;
    margin-bottom:25px;
}

.logo i{
    font-size:55px;
    color:#667eea;
}

.form-control{
    border-radius:8px;
}

.btn-primary{
    border:none;
    border-radius:8px;
    padding:10px;
    font-weight:bold;
}

</style>

</head>

<body>

<div class="login-card">

<div class="logo">

<i class="fas fa-university"></i>

<h3>University MIS</h3>

<p class="text-muted">SSO Module Login</p>

</div>

<?php if(!empty($error)){ ?>

<div class="alert alert-danger">

<?php echo $error; ?>

</div>

<?php } ?>

<form method="POST">

<div class="mb-3">

<label class="form-label">Username</label>

<input
type="text"
name="username"
class="form-control"
required>

</div>

<div class="mb-3">

<label class="form-label">Password</label>

<input
type="password"
name="password"
class="form-control"
required>

</div>

<button class="btn btn-primary w-100">

<i class="fas fa-sign-in-alt"></i>

Login

</button>

</form>

<div class="text-center mt-3">

<small>

Demo Login

<br>

Username : <b>admin</b>

<br>

Password : <b>admin123</b>

</small>

</div>

</div>

</body>

</html>