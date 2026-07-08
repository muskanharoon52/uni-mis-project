<?php

declare(strict_types=1);

require __DIR__ . '/config/database.php';
require __DIR__ . '/includes/helpers.php';
require __DIR__ . '/includes/auth.php';

require_login(['teacher']);

$pageTitle = 'Exams';
$activePage = 'exams';

$exams = db()->query('SELECT * FROM exams ORDER BY exam_id DESC LIMIT 50')->fetchAll();

require __DIR__ . '/includes/header.php';
?>

<div class="page">
    <div class="page-head">
        <div>
            <h2>Exams</h2>
            <p>View registered exam definitions inside the SBE module.</p>
        </div>
        <div class="actions">
            <a class="btn btn-ghost" href="index.php">← Dashboard</a>
        </div>
    </div>

    <div class="table-card page-section">
        <h3 style="margin:0 0 4px;">Registered Exams</h3>
        <p class="small" style="margin:0 0 16px;">Current list of all manual and random exam configurations.</p>
        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>Code</th>
                        <th>Title</th>
                        <th>Type</th>
                        <th>Course</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($exams)): ?>
                    <tr>
                        <td colspan="5">
                            <div class="empty-state">
                                <span class="empty-icon">📭</span>
                                <p>No registered exams found.</p>
                            </div>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($exams as $exam): ?>
                        <tr>
                            <td><span class="badge manual"><?= e($exam['exam_code']) ?></span></td>
                            <td style="font-weight:600; color:var(--text-strong);"><?= e($exam['title']) ?></td>
                            <td class="small"><?= e($exam['exam_type']) ?></td>
                            <td class="small">Course #<?= (int) $exam['course_id'] ?></td>
                            <td><span class="badge <?= e($exam['status']) ?>"><?= e($exam['status']) ?></span></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
