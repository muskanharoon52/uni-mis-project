<?php
// notifications/preview.php - AJAX endpoint: live recipient count preview.
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once __DIR__ . '/../config/db_connect.php';
require_once __DIR__ . '/../modules/sso/includes/auth.php';

if (!isLoggedIn()) { http_response_code(403); header('Content-Type: application/json'); echo json_encode(['error' => 'Unauthorized']); exit; }
require_once __DIR__ . '/_helpers.php';
global $conn;

$audience = $_GET['audience'] ?? 'Both';
$fScope = $_GET['faculty_scope'] ?? 'All';
$sScope = $_GET['student_scope'] ?? 'All';
$fDepts = isset($_GET['faculty_depts']) ? array_map('intval', (array)$_GET['faculty_depts']) : [];
$fBatches = isset($_GET['faculty_batches']) ? array_map('intval', (array)$_GET['faculty_batches']) : [];
$sDepts = isset($_GET['student_depts']) ? array_map('intval', (array)$_GET['student_depts']) : [];
$sBatches = isset($_GET['student_batches']) ? array_map('intval', (array)$_GET['student_batches']) : [];

if (!in_array($audience, ['Faculty', 'Students', 'Both'], true)) $audience = 'Both';

$result = notif_compute($conn, $audience, $fScope, $fDepts, $fBatches, $sScope, $sDepts, $sBatches);

header('Content-Type: application/json');
echo json_encode([
    'faculty_count' => $result['faculty_count'],
    'student_count' => $result['student_count'],
    'total' => $result['total'],
]);
