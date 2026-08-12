<?php

declare(strict_types=1);

require __DIR__ . '/config/database.php';
require __DIR__ . '/includes/helpers.php';
require __DIR__ . '/includes/auth.php';

require_login(['Teacher']);

$pageTitle = 'Datesheets';
$activePage = 'datesheets';
$db = db();

$departmentId = !empty($_GET['department_id']) ? (int) $_GET['department_id'] : 0;
$semesterId   = !empty($_GET['semester_id']) ? (int) $_GET['semester_id'] : 0;
$examType     = !empty($_GET['exam_type']) ? (string) $_GET['exam_type'] : '';
$examType     = ($examType === 'Mid' || $examType === 'Final') ? $examType : '';

$sql = 'SELECT d.*, dept.department_name, sem.semester_name
        FROM sbe_datesheets d
        LEFT JOIN departments dept ON dept.department_id = d.department_id
        LEFT JOIN semesters sem ON sem.semester_id = d.semester_id
        WHERE 1=1';
$params = [];
if ($departmentId > 0) {
    $sql .= ' AND d.department_id = ?';
    $params[] = $departmentId;
}
if ($semesterId > 0) {
    $sql .= ' AND d.semester_id = ?';
    $params[] = $semesterId;
}
if ($examType !== '') {
    $sql .= ' AND d.exam_type = ?';
    $params[] = $examType;
}
$sql .= ' ORDER BY d.created_at DESC';

$projectUrl = '/' . basename(dirname(__DIR__, 2));

$stmt = $db->prepare($sql);
$stmt->execute($params);
$datesheets = $stmt->fetchAll();

$departments = $db->query('SELECT department_id, department_name FROM departments ORDER BY department_name')->fetchAll();
$semesters   = $db->query('SELECT semester_id, semester_name FROM semesters ORDER BY semester_name')->fetchAll();

require __DIR__ . '/includes/header.php';
?>

<div class="page animate-in">
    <div class="page-head">
        <div>
            <h2>Exam Datesheets</h2>
            <p>Datesheets published by the examination department for Mid and Final exams.</p>
        </div>
    </div>

    <div class="card page-section">
        <form method="get" style="display:flex; gap:12px; flex-wrap:wrap; align-items:flex-end;">
            <div class="field" style="min-width:180px;">
                <label for="department_id">Department</label>
                <select id="department_id" name="department_id">
                    <option value="">All Departments</option>
                    <?php foreach ($departments as $dept): ?>
                        <option value="<?= (int) $dept['department_id'] ?>" <?= $departmentId === (int) $dept['department_id'] ? 'selected' : '' ?>>
                            <?= e($dept['department_name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="field" style="min-width:180px;">
                <label for="semester_id">Semester</label>
                <select id="semester_id" name="semester_id">
                    <option value="">All Semesters</option>
                    <?php foreach ($semesters as $sem): ?>
                        <option value="<?= (int) $sem['semester_id'] ?>" <?= $semesterId === (int) $sem['semester_id'] ? 'selected' : '' ?>>
                            <?= e($sem['semester_name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="field" style="min-width:150px;">
                <label for="exam_type">Exam Type</label>
                <select id="exam_type" name="exam_type">
                    <option value="">All</option>
                    <option value="Mid" <?= $examType === 'Mid' ? 'selected' : '' ?>>Mid</option>
                    <option value="Final" <?= $examType === 'Final' ? 'selected' : '' ?>>Final</option>
                </select>
            </div>
            <button type="submit" class="btn btn-solid">Apply Filters</button>
            <a class="btn" href="datesheets.php">Reset</a>
        </form>
    </div>

    <div class="page-section">
        <?php if (empty($datesheets)): ?>
            <div class="empty-state">
                <div class="empty-icon">&#128196;</div>
                <h3>No datesheets published yet</h3>
                <p>When the examination department publishes datesheets for Mid or Final exams, they will appear here.</p>
            </div>
        <?php else: ?>
            <div class="table-card">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Title</th>
                            <th>Type</th>
                            <th>Department</th>
                            <th>Semester</th>
                            <th>Published</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($datesheets as $ds): ?>
                            <tr>
                                <td><strong><?= e($ds['title']) ?></strong></td>
                                <td><span class="badge badge-<?= e(strtolower((string) $ds['exam_type'])) ?>"><?= e($ds['exam_type']) ?></span></td>
                                <td><?= $ds['department_name'] ? e($ds['department_name']) : 'All' ?></td>
                                <td><?= $ds['semester_name'] ? e($ds['semester_name']) : 'All' ?></td>
                                <td><?= e(date('M d, Y h:i A', strtotime((string) $ds['created_at']))) ?></td>
                                <td>
                                    <a class="btn btn-ghost btn-sm" href="<?= e($projectUrl . '/' . $ds['file_path']) ?>" target="_blank" rel="noopener">
                                        &#128196; View PDF
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
