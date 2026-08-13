<?php
// Lightweight login validation used by the login page's "Remember me" feature.
// Returns JSON only; does not start a session or set any cookies.
require_once __DIR__ . '/../../config/db_connect.php';

if (!headers_sent()) {
    header('Content-Type: application/json');
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

$username = trim($_POST['username'] ?? '');
$password = (string)($_POST['password'] ?? '');

if ($username === '' || $password === '') {
    echo json_encode(['success' => false]);
    exit;
}

$conn = getConnection();
$u = mysqli_real_escape_string($conn, $username);
$q = mysqli_query($conn, "SELECT password_hash FROM users WHERE username = '$u' OR login_id = '$u' LIMIT 1");
$row = $q ? mysqli_fetch_assoc($q) : null;

$ok = false;
if ($row) {
    $hash = (string)$row['password_hash'];
    $ok = ($hash !== '' && (password_verify($password, $hash) || $password === $hash));
}

echo json_encode(['success' => $ok]);
