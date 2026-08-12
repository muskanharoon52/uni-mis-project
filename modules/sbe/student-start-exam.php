<?php

declare(strict_types=1);

require __DIR__ . '/config/database.php';
require __DIR__ . '/includes/helpers.php';
require __DIR__ . '/includes/auth.php';

require_login(['Student']);

$db = db();
$student = current_user();
$studentId = (int) $student['student_id'];
$pageTitle = 'Start Exam Room';
$activePage = 'student_start_exam';

$scope = $db->prepare('SELECT s.batch_year, p.department_id FROM students s LEFT JOIN programs p ON p.program_id = s.program_id WHERE s.student_id = :sid');
$scope->execute([':sid' => $studentId]);
$studentRow = $scope->fetch();
$deptId = (int) ($studentRow['department_id'] ?? 0);
$batchYear = (int) ($studentRow['batch_year'] ?? 0);

if ($deptId > 0) {
    $availableStmt = $db->prepare("SELECT es.schedule_id, es.exam_id, es.section, es.exam_date, es.start_time, es.end_time, es.location, es.status AS sched_status, e.exam_code, e.title, e.duration_minutes, e.total_questions, e.total_marks, e.exam_type FROM sbe_exam_schedule es INNER JOIN sbe_exams e ON e.exam_id = es.exam_id WHERE e.status = 'Published' AND es.status IN ('Scheduled', 'Ongoing') AND ((e.department_id IS NULL AND e.batch_year IS NULL) OR (e.department_id = :dept AND (e.batch_year IS NULL OR e.batch_year = :batch))) ORDER BY es.exam_date ASC, es.start_time ASC");
    $availableStmt->execute([':dept' => $deptId, ':batch' => $batchYear]);
} else {
    $availableStmt = $db->prepare("SELECT es.schedule_id, es.exam_id, es.section, es.exam_date, es.start_time, es.end_time, es.location, es.status AS sched_status, e.exam_code, e.title, e.duration_minutes, e.total_questions, e.total_marks, e.exam_type FROM sbe_exam_schedule es INNER JOIN sbe_exams e ON e.exam_id = es.exam_id WHERE e.status = 'Published' AND es.status IN ('Scheduled', 'Ongoing') ORDER BY es.exam_date ASC, es.start_time ASC");
    $availableStmt->execute();
}
$available = $availableStmt->fetchAll();

$message = $_SESSION['message'] ?? null;
unset($_SESSION['message']);

require __DIR__ . '/includes/header.php';
?>

<div class="page animate-in">
    <?php if ($message): ?>
        <div class="alert alert-error" style="margin-bottom:18px;"><?= e($message) ?></div>
    <?php endif; ?>
    <div class="page-head">
        <div>
            <h2>Start Exam Room</h2>
            <p>Select your scheduled exam session below to begin your online paper attempt.</p>
        </div>
        <div class="actions">
            <a class="btn btn-ghost" href="student-home.php">&larr; Student Dashboard</a>
        </div>
    </div>

    <div class="page-section">
        <h3 class="mb-16">Scheduled Exam Sessions</h3>

        <?php if (empty($available)): ?>
            <div class="card">
                <div class="empty-state">
                    <span class="empty-icon">&#128233;</span>
                    <p>No exams currently scheduled for your department and batch.</p>
                </div>
            </div>
        <?php else: ?>
            <div class="exam-cards">
                <?php foreach ($available as $row):
                    $isToday = $row['exam_date'] === date('Y-m-d');
                    $accentStyle = $isToday ? 'border-top: 4px solid var(--warning);' : 'border-top: 4px solid var(--accent);';
                ?>
                    <div class="exam-card" style="<?= $accentStyle ?>">
                        <div class="exam-card-header">
                            <div>
                                <span class="badge badge-<?= e(strtolower((string) $row['exam_type'])) ?>" style="margin-bottom:6px;"><?= e($row['exam_code']) ?></span>
                                <h4 class="exam-card-title"><?= e($row['title']) ?></h4>
                            </div>
                            <?php if ($isToday): ?>
                                <span class="badge badge-ongoing">Today</span>
                            <?php endif; ?>
                        </div>

                        <div class="exam-card-meta">
                            <span>Date: <strong><?= e($row['exam_date']) ?></strong></span>
                            <span>Time: <strong><?= e(substr((string) $row['start_time'], 0, 5)) ?> &ndash; <?= e(substr((string) $row['end_time'], 0, 5)) ?></strong></span>
                            <span>Location: <strong><?= e($row['location']) ?></strong></span>
                            <span>Duration: <strong><?= (int) $row['duration_minutes'] ?> mins</strong></span>
                            <span>Questions: <strong><?= (int) $row['total_questions'] ?> MCQs</strong></span>
                            <span>Status: <strong><?= e($row['sched_status']) ?></strong></span>
                        </div>

                        <div class="exam-card-actions">
                            <a class="btn btn-primary" href="take-exam.php?schedule_id=<?= (int) $row['schedule_id'] ?>" style="width:100%; justify-content:center; background:<?= $isToday ? 'linear-gradient(135deg, var(--warning), #d97706)' : 'linear-gradient(135deg, var(--accent), var(--accent-2))' ?>;">
                                Launch Exam Room &rarr;
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
