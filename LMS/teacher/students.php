<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';

$user = require_role('teacher');
$active = 'students';
$pageTitle = 'Students';

$stmt = db()->prepare(
    'SELECT DISTINCT u.full_name, u.email, d.department_name
     FROM users u
     JOIN lms_enrollments e ON e.student_user_id = u.user_id
     JOIN courses c ON c.course_id = e.course_id
     LEFT JOIN departments d ON d.department_id = u.department_id
     WHERE c.teacher_id = ?
     ORDER BY u.full_name'
);
$stmt->execute([$user['teacher_id']]);
$students = $stmt->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>
<div class="card">
    <div class="card-header"><h3>Students</h3></div>
    <div class="table-responsive">
        <table>
            <tr><th>Name</th><th>Email</th><th>Department</th></tr>
            <?php foreach ($students as $student): ?>
                <tr><td><?= e($student['full_name']) ?></td><td><?= e($student['email']) ?></td><td><?= e($student['department_name'] ?? 'N/A') ?></td></tr>
            <?php endforeach; ?>
        </table>
    </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
