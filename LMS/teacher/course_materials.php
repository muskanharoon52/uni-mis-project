<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';

$user = require_role('teacher');
$active = 'materials';
$pageTitle = 'Course Materials';

$stmt = db()->prepare(
    'SELECT l.*, c.course_code
     FROM lms_lectures l
     JOIN courses c ON c.course_id = l.course_id
     WHERE c.teacher_id = ?
     ORDER BY l.lecture_id DESC'
);
$stmt->execute([$user['teacher_id']]);
$materials = $stmt->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>
<div class="card">
    <div class="card-header"><h3>Course Materials</h3></div>
    <div class="table-responsive">
        <table>
            <tr><th>Course</th><th>Title</th><th>Date</th><th>File</th></tr>
            <?php foreach ($materials as $material): ?>
                <tr><td><?= e($material['course_code']) ?></td><td><?= e($material['title']) ?></td><td><?= e($material['lecture_date']) ?></td><td><a href="<?= app_url($material['file_path']) ?>" target="_blank">Download</a></td></tr>
            <?php endforeach; ?>
        </table>
    </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
