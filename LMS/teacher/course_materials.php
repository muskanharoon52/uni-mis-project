<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';

$user = require_role('teacher');
$active = 'materials';
$pageTitle = 'Course Materials';

$stmt = db()->prepare(
    'SELECT l.*, c.code
     FROM lectures l
     JOIN courses c ON c.id = l.course_id
     WHERE c.teacher_id = ?
     ORDER BY l.id DESC'
);
$stmt->execute([$user['id']]);
$materials = $stmt->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>
<section class="dashboard-section">
    <header>Course Materials</header>
    <div class="table-card compact-table">
        <table>
            <tr><th>Course</th><th>Title</th><th>Date</th><th>File</th></tr>
            <?php foreach ($materials as $material): ?>
                <tr><td><?= e($material['code']) ?></td><td><?= e($material['title']) ?></td><td><?= e($material['lecture_date']) ?></td><td><a href="<?= app_url($material['file_path']) ?>" target="_blank">Download</a></td></tr>
            <?php endforeach; ?>
        </table>
    </div>
</section>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
