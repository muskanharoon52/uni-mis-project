<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';

$user = require_role('student');

$appId = !empty($_GET['app_id']) ? (int) $_GET['app_id'] : 0;
if ($appId <= 0) {
    header('Location: examination.php');
    exit;
}

$studentStmt = db()->prepare('SELECT student_id FROM students WHERE user_id = ? LIMIT 1');
$studentStmt->execute([(int) $user['id']]);
$studentId = (int) ($studentStmt->fetchColumn() ?: 0);

if ($studentId <= 0) {
    header('Location: examination.php');
    exit;
}

$stmt = db()->prepare(
    'SELECT app.status, app.transcript_path, app.student_id
     FROM result_publish_applications app
     WHERE app.id = ? AND app.student_id = ?'
);
$stmt->execute([$appId, $studentId]);
$app = $stmt->fetch();

if (!$app || $app['status'] !== 'approved' || empty($app['transcript_path'])) {
    header('Location: examination.php');
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
