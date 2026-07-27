<?php
require_once __DIR__ . '/../../config/db_connect.php';
require_once __DIR__ . '/../sso/includes/auth.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    if (loginUser($username, $password)) {
        header('Location: dashboard.php');
        exit;
    } else {
        $error = 'Invalid username or password.';
    }
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Sign In | Finance Module</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= BASE_URL ?>modules/lms/public/assets/style.css?v=<?= filemtime(__DIR__ . '/../lms/public/assets/style.css') ?>">
</head>
<body class="login-page">

<div class="login-container">
    <section class="login-hero">
        <div class="login-brand">
            <div class="brand-mark">FIN</div>
        </div>
        <div class="login-title">
            <h1>University Finance Management</h1>
            <p>Manage fee structures, student billing, payments, receipts, and financial reporting from a single dashboard.</p>
        </div>
        <div class="hero-points">
            <div class="hero-point">
                <div class="role-pill">FEE</div>
                <div>
                    <strong>Fee Management</strong>
                    <p class="small">Define fee heads, structures, and generate student invoices.</p>
                </div>
            </div>
            <div class="hero-point">
                <div class="role-pill">PAY</div>
                <div>
                    <strong>Payments & Receipts</strong>
                    <p class="small">Record payments, issue receipts, and track collections.</p>
                </div>
            </div>
            <div class="hero-point">
                <div class="role-pill">RPT</div>
                <div>
                    <strong>Financial Reports</strong>
                    <p class="small">View summaries, outstanding dues, and collection analytics.</p>
                </div>
            </div>
        </div>
    </section>

    <aside class="login-panel">
        <div style="font-size:2rem;font-weight:800;color:var(--accent);margin-bottom:18px;">&#9733;</div>
        <h3>Sign in to Finance</h3>
        <p class="muted" style="margin-bottom:22px;">Use your University account to access finance tools.</p>

        <?php if ($error): ?>
            <div class="alert alert-error" style="margin-bottom:16px;"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="post" onsubmit="setLoading(this)">
            <div class="field" style="margin-bottom:16px;">
                <label>Username</label>
                <input type="text" name="username" required placeholder="Enter your username" autocomplete="username">
            </div>

            <div class="field password-field" style="margin-bottom:24px;">
                <label>Password</label>
                <input type="password" name="password" id="pass-field" required placeholder="Enter password" autocomplete="current-password">
                <button class="password-toggle" type="button" onclick="togglePass()">&#128065;</button>
            </div>

            <button class="btn btn-primary" type="submit" style="width:100%;min-height:44px;">Sign In</button>
        </form>
    </aside>
</div>

<script>
function togglePass() {
    const input = document.getElementById('pass-field');
    input.type = input.type === 'password' ? 'text' : 'password';
}
function setLoading(form) {
    const btn = form.querySelector('button[type="submit"]');
    btn.classList.add('btn-loading');
    btn.disabled = true;
}
</script>
</body>
</html>
