<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';

$user = require_role('teacher');
$active = 'students';
$pageTitle = 'Students';

$stmt = db()->prepare(
    'SELECT DISTINCT u.name, u.email, u.department, u.program
     FROM users u
     JOIN enrollments e ON e.student_id = u.id
     JOIN courses c ON c.id = e.course_id
     WHERE c.teacher_id = ?
     ORDER BY u.name'
);
$stmt->execute([$user['id']]);
$students = $stmt->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>
<div class="card">
    <div class="card-header"><h3>Students</h3></div>
    <div class="table-responsive">
        <table>
            <tr><th>Name</th><th>Email</th><th>Department</th><th>Program</th></tr>
            <?php foreach ($students as $student): ?>
                <tr><td><?= e($student['name']) ?></td><td><?= e($student['email']) ?></td><td><?= e($student['department']) ?></td><td><?= e($student['program']) ?></td></tr>
            <?php endforeach; ?>
        </table>
    </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
