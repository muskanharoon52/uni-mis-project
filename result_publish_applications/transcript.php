<?php
// result_publish_applications/transcript.php - Stream an approved result transcript PDF.

require_once __DIR__ . '/../config/db_connect.php';
require_once __DIR__ . '/../modules/sso/includes/auth.php';
require_once __DIR__ . '/../modules/sbe/config/database.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

requireLogin();

$appId = !empty($_GET['app_id']) ? (int) $_GET['app_id'] : 0;

if ($appId <= 0) {
    header('Location: index.php');
    exit;
}

$db = db();
$stmt = $db->prepare(
    'SELECT app.status, app.transcript_path, app.student_id
     FROM result_publish_applications app
     WHERE app.id = :id'
);
$stmt->execute([':id' => $appId]);
$app = $stmt->fetch();

if (!$app || $app['status'] !== 'approved' || empty($app['transcript_path'])) {
    header('Location: index.php');
    exit;
}

$filePath = $_SERVER['DOCUMENT_ROOT'] . '/uni-mis-project/' . $app['transcript_path'];

if (!is_file($filePath)) {
    http_response_code(404);
    exit('Transcript file not found.');
}

header('Content-Type: application/pdf');
header('Content-Disposition: inline; filename="' . basename($filePath) . '"');
header('Content-Length: ' . filesize($filePath));
header('Cache-Control: private, no-store');
readfile($filePath);
exit;
