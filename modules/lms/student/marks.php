<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';

$user = require_role('student');
$active = 'marks';
$pageTitle = 'Internal Marks';
$components = internal_mark_components();
$rows = internal_mark_rows_for_student((int) $user['id']);

require_once __DIR__ . '/../includes/header.php';
?>
<div class="card">
    <div class="card-header"><h3>Internal Marks</h3></div>
    <div class="table-responsive">
    <table class="marks-table">
        <tr>
            <th rowspan="2">Course ID</th>
            <th rowspan="2">Title</th>
            <th colspan="3">Assignments</th>
            <th colspan="3">Tests</th>
            <th rowspan="2">Presentation</th>
            <th rowspan="2">Major Assignment</th>
            <th rowspan="2">Mid Term</th>
            <th rowspan="2">Total</th>
            <th rowspan="2">Status</th>
        </tr>
        <tr><th>1</th><th>2</th><th>3</th><th>1</th><th>2</th><th>3</th></tr>
        <?php foreach ($rows as $row): ?>
            <tr>
                <td><?= e($row['code']) ?></td>
                <td><?= e($row['title']) ?></td>
                <?php foreach ($components as $component => $label): ?>
                    <td><?= e((string) $row['marks'][$component]) ?></td>
                <?php endforeach; ?>
                <td><?= e(number_format((float) internal_mark_total($row), 2)) ?></td>
                <td><span class="badge <?= $row['is_finalized'] ? 'badge-inactive' : 'badge-active' ?>"><?= $row['is_finalized'] ? 'Finalized' : 'Not Finalized' ?></span></td>
            </tr>
        <?php endforeach; ?>
    </table>
    </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
