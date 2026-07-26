<?php
// fee_management/structure_delete.php - Delete Fee Structure

require_once __DIR__ . '/../config/db_connect.php';
require_once __DIR__ . '/../includes/auth.php';

if (!isLoggedIn()) {
    header('Location: ' . BASE_URL . 'login.php');
    exit;
}

$user = getCurrentUser();
$role = $user['role_name'] ?? 'User';

if (!in_array($role, ['sso', 'admin'])) {
    header('Location: ' . BASE_URL . 'dashboard.php');
    exit;
}

$conn = getConnection();
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id > 0) {
    mysqli_query($conn, "SET FOREIGN_KEY_CHECKS = 0");
    mysqli_query($conn, "DELETE FROM fee_structures WHERE fee_structure_id = $id");
    mysqli_query($conn, "SET FOREIGN_KEY_CHECKS = 1");
}

header('Location: index.php?tab=structures&deleted=1');
exit;
?>