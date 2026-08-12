<?php
// examination/results/accept.php - Accept an SBE result and request SSO to publish it.

require_once '../../config/db_connect.php';
require_once '../../modules/sbe/config/database.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (empty($_SESSION['user_id'])) {
    header('Location: ' . (defined('BASE_URL') ? BASE_URL : '/uni-mis-project/') . 'login.php');
    exit;
}

$db = db();

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_POST['exam_result_id'])) {
    $_SESSION['error'] = 'Invalid request.';
    header('Location: index.php');
    exit;
}

$examResultId = (int) $_POST['exam_result_id'];
$userId = (int) $_SESSION['user_id'];

if ($examResultId <= 0) {
    $_SESSION['error'] = 'Invalid result.';
    header('Location: index.php');
    exit;
}

$stmt = $db->prepare(
    'SELECT er.exam_result_id, er.status, er.student_id, er.exam_id,
            s.current_session_id AS session_id, p.department_id
     FROM sbe_exam_results er
     JOIN students s ON s.student_id = er.student_id
     LEFT JOIN programs p ON p.program_id = s.program_id
     WHERE er.exam_result_id = :id'
);
$stmt->execute([':id' => $examResultId]);
$result = $stmt->fetch();

if (!$result) {
    $_SESSION['error'] = 'Result not found.';
    header('Location: index.php');
    exit;
}

if ($result['status'] !== 'Draft') {
    $_SESSION['error'] = 'Only draft results awaiting acceptance can be sent to SSO.';
    header('Location: index.php');
    exit;
}

try {
    $db->beginTransaction();

    $db->prepare('UPDATE sbe_exam_results SET status = :status, published_at = NULL WHERE exam_result_id = :id')
        ->execute([':status' => 'Pending', ':id' => $examResultId]);

    $upsert = $db->prepare(
        'INSERT INTO result_publish_applications
            (exam_result_id, student_id, exam_id, department_id, session_id, status, requested_by, requested_at)
         VALUES (:exam_result_id, :student_id, :exam_id, :department_id, :session_id, :status, :requested_by, :requested_at)
         ON DUPLICATE KEY UPDATE
            status = VALUES(status),
            transcript_path = NULL,
            requested_by = VALUES(requested_by),
            requested_at = VALUES(requested_at),
            reviewed_by = NULL,
            reviewed_at = NULL,
            reviewer_remarks = NULL'
    );
    $upsert->execute([
        ':exam_result_id' => $examResultId,
        ':student_id'     => (int) $result['student_id'],
        ':exam_id'        => (int) $result['exam_id'],
        ':department_id'  => $result['department_id'] !== null ? (int) $result['department_id'] : null,
        ':session_id'     => $result['session_id'] !== null ? (int) $result['session_id'] : null,
        ':status'         => 'pending',
        ':requested_by'   => $userId,
        ':requested_at'   => date('Y-m-d H:i:s'),
    ]);

    $db->commit();
} catch (Throwable $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    $_SESSION['error'] = 'Could not send publish request to SSO. Please try again.';
    header('Location: index.php');
    exit;
}

require_once '../../includes/activity.php';
log_activity('Examination', 'Result Accept', 'sbe_exam_results', $examResultId, 'Result sent to SSO for publication (result_id ' . $examResultId . ')');

$_SESSION['success'] = 'Result accepted. A publish request has been sent to SSO for approval.';
header('Location: index.php');
exit;
