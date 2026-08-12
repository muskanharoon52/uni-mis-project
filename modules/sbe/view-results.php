<?php

declare(strict_types=1);

require __DIR__ . '/config/database.php';
require __DIR__ . '/includes/helpers.php';
require __DIR__ . '/includes/auth.php';

require_login(['Teacher']);

$pageTitle = 'View Results';
$activePage = 'view_results';
$db = db();

$departmentId = !empty($_GET['department_id']) ? (int) $_GET['department_id'] : 0;
$courseId     = !empty($_GET['course_id']) ? (int) $_GET['course_id'] : 0;
$sectionId    = !empty($_GET['section_id']) ? (int) $_GET['section_id'] : 0;
$semesterId   = !empty($_GET['semester_id']) ? (int) $_GET['semester_id'] : 0;
$submitted    = isset($_GET['course_id']);

$departments = $db->query('SELECT department_id, department_name FROM departments WHERE status = "Active" ORDER BY department_name')->fetchAll();
$courses     = $db->query('SELECT c.course_id, c.course_code, c.course_title, c.department_id FROM courses c WHERE c.status = "Active" AND EXISTS (SELECT 1 FROM sbe_exams se WHERE se.course_id = c.course_id) ORDER BY c.course_code')->fetchAll();
$sections    = $db->query('SELECT sec.section_id, sec.section_name, p.department_id FROM sections sec LEFT JOIN programs p ON p.program_id = sec.program_id WHERE sec.status = "Active" ORDER BY sec.section_name')->fetchAll();
$semesters   = $db->query('SELECT semester_id, semester_name FROM semesters ORDER BY semester_number')->fetchAll();

$selectedCourse = null;
$roster = [];
$attempted = 0;
$passed = 0;

if ($submitted && $courseId > 0) {
    $courseStmt = $db->prepare('SELECT course_id, course_code, course_title, department_id FROM courses WHERE course_id = :id');
    $courseStmt->execute([':id' => $courseId]);
    $selectedCourse = $courseStmt->fetch();
}

if ($selectedCourse) {
    $sql = "SELECT s.student_id, s.roll_no, s.full_name, s.batch_year,
                   sem.semester_name, sec.section_name,
                   r.exam_result_id, r.exam_id, r.obtained_marks, r.total_marks, r.percentage,
                   r.pass_fail_status, r.status AS result_status, r.published_at,
                   r.exam_code, r.exam_title, r.exam_type
            FROM students s
            LEFT JOIN semesters sem ON sem.semester_id = s.current_semester_id
            LEFT JOIN sections sec ON sec.section_id = s.section_id
            JOIN programs p ON p.program_id = s.program_id
            LEFT JOIN (
                SELECT r2.*, e2.exam_code, e2.title AS exam_title, e2.exam_type
                FROM sbe_exam_results r2
                JOIN sbe_exams e2 ON e2.exam_id = r2.exam_id
                WHERE e2.course_id = :cid
                  AND r2.student_exam_id = (
                      SELECT MAX(r3.student_exam_id)
                      FROM sbe_exam_results r3
                      JOIN sbe_exams e3 ON e3.exam_id = r3.exam_id
                      WHERE e3.course_id = :cid2 AND r3.student_id = r2.student_id
                  )
            ) r ON r.student_id = s.student_id
            WHERE s.status = 'Active'
              AND p.department_id = COALESCE(:dept, p.department_id)
              AND s.section_id = COALESCE(:section, s.section_id)
              AND s.current_semester_id = COALESCE(:semester, s.current_semester_id)
            ORDER BY s.roll_no";

    $stmt = $db->prepare($sql);
    $stmt->execute([
        ':cid'     => $courseId,
        ':cid2'    => $courseId,
        ':dept'    => $departmentId > 0 ? $departmentId : null,
        ':section' => $sectionId > 0 ? $sectionId : null,
        ':semester' => $semesterId > 0 ? $semesterId : null,
    ]);
    $roster = $stmt->fetchAll();

    foreach ($roster as $row) {
        if ($row['exam_result_id'] !== null) {
            $attempted++;
            if ($row['pass_fail_status'] === 'Pass') {
                $passed++;
            }
        }
    }
}

if (!function_exists('grade_letter')) {
    function grade_letter(?float $pct): string
    {
        if ($pct === null) {
            return '-';
        }
        if ($pct >= 90) return 'A+';
        if ($pct >= 80) return 'A';
        if ($pct >= 70) return 'B';
        if ($pct >= 60) return 'C';
        if ($pct >= 50) return 'D';
        return 'F';
    }
}

require __DIR__ . '/includes/header.php';
?>

<div class="page animate-in">
    <div class="page-head">
        <div>
            <h2>View Results</h2>
            <p>Pick a subject and refine by department, section or semester to see the full list of students and their latest result.</p>
        </div>
    </div>

    <div class="card page-section">
        <form method="get" style="display:flex; gap:12px; flex-wrap:wrap; align-items:flex-end;" id="result-filter-form">
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
            <div class="field" style="min-width:220px;">
                <label for="course_id">Subject *</label>
                <select id="course_id" name="course_id" required>
                    <option value="">Select subject</option>
                    <?php foreach ($courses as $course): ?>
                        <option value="<?= (int) $course['course_id'] ?>" data-dept="<?= (int) $course['department_id'] ?>" <?= $courseId === (int) $course['course_id'] ? 'selected' : '' ?>>
                            <?= e($course['course_code']) ?> &mdash; <?= e(mb_strimwidth($course['course_title'], 0, 40, '...')) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="field" style="min-width:140px;">
                <label for="section_id">Section</label>
                <select id="section_id" name="section_id">
                    <option value="">All Sections</option>
                    <?php foreach ($sections as $sec): ?>
                        <option value="<?= (int) $sec['section_id'] ?>" data-dept="<?= (int) ($sec['department_id'] ?? 0) ?>" <?= $sectionId === (int) $sec['section_id'] ? 'selected' : '' ?>>
                            <?= e($sec['section_name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="field" style="min-width:160px;">
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
            <button type="submit" class="btn btn-solid">View Students</button>
            <a class="btn" href="view-results.php">Reset</a>
        </form>
    </div>

    <?php if ($selectedCourse): ?>
        <div class="stats-grid page-section animate-in">
            <div class="stat-card-v2">
                <div class="stat-icon purple">&#128101;</div>
                <div class="stat-label">Students in Class</div>
                <div class="stat-value"><?= number_format(count($roster)) ?></div>
            </div>
            <div class="stat-card-v2">
                <div class="stat-icon green">&#9997;</div>
                <div class="stat-label">Attempted</div>
                <div class="stat-value"><?= number_format($attempted) ?></div>
            </div>
            <div class="stat-card-v2">
                <div class="stat-icon amber">&#127942;</div>
                <div class="stat-label">Passed</div>
                <div class="stat-value"><?= number_format($passed) ?></div>
            </div>
            <div class="stat-card-v2">
                <div class="stat-icon rose">&#128202;</div>
                <div class="stat-label">Pass Rate</div>
                <div class="stat-value"><?= $attempted > 0 ? round(($passed / $attempted) * 100) . '%' : '-' ?></div>
            </div>
        </div>

        <div class="page-section">
            <div class="page-head" style="padding:0; margin-bottom:14px;">
                <div>
                    <h3 style="margin:0;"><?= e($selectedCourse['course_code']) ?> &mdash; <?= e($selectedCourse['course_title']) ?></h3>
                    <p class="small" style="margin:2px 0 0;">Latest SBE result per student in this subject. Click a student for the full paper detail.</p>
                </div>
            </div>

            <?php if (empty($roster)): ?>
                <div class="empty-state">
                    <div class="empty-icon">&#128202;</div>
                    <h3>No students found</h3>
                    <p>No active students match the selected department, section and semester.</p>
                </div>
            <?php else: ?>
                <div class="table-card">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Roll No</th>
                                <th>Student</th>
                                <th>Section</th>
                                <th>Semester</th>
                                <th>Exam</th>
                                <th>Marks</th>
                                <th>%</th>
                                <th>Grade</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($roster as $row): ?>
                                <?php $hasResult = $row['exam_result_id'] !== null; ?>
                                <tr>
                                    <td><?= e((string) $row['roll_no']) ?></td>
                                    <td>
                                        <strong><?= e($row['full_name']) ?></strong>
                                        <?php if ($row['batch_year']): ?>
                                            <small class="muted" style="display:block;">Batch <?= e((string) $row['batch_year']) ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= $row['section_name'] ? e($row['section_name']) : '-' ?></td>
                                    <td><?= $row['semester_name'] ? e($row['semester_name']) : '-' ?></td>
                                    <td>
                                        <?php if ($hasResult): ?>
                                            <span class="badge badge-<?= e(strtolower((string) $row['exam_type'])) ?>"><?= e($row['exam_code']) ?></span>
                                            <small class="muted" style="display:block;"><?= e(mb_strimwidth((string) $row['exam_title'], 0, 30, '...')) ?></small>
                                        <?php else: ?>
                                            <span class="muted">&mdash;</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($hasResult): ?>
                                            <strong><?= number_format((float) $row['obtained_marks'], 2) ?></strong> / <?= number_format((float) $row['total_marks'], 2) ?>
                                        <?php else: ?>
                                            &mdash;
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?= $hasResult ? number_format((float) $row['percentage'], 1) . '%' : '&mdash;' ?>
                                    </td>
                                    <td>
                                        <?php if ($hasResult): ?>
                                            <span class="badge badge-<?= e(strtolower((string) $row['pass_fail_status'])) ?>"><?= grade_letter((float) $row['percentage']) ?></span>
                                        <?php else: ?>
                                            &mdash;
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($hasResult): ?>
                                            <span class="badge badge-<?= $row['pass_fail_status'] === 'Pass' ? 'pass' : 'fail' ?>"><?= e($row['pass_fail_status']) ?></span>
                                            <?php if ($row['result_status']): ?>
                                                <small class="muted" style="display:block;"><?= e($row['result_status']) ?></small>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <span class="badge badge-inactive">Not Attempted</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($hasResult): ?>
                                            <a class="btn btn-ghost btn-sm" href="student-result.php?student_id=<?= (int) $row['student_id'] ?>&course_id=<?= (int) $courseId ?>">View Result</a>
                                        <?php else: ?>
                                            <span class="muted">&mdash;</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <div class="empty-state page-section">
            <div class="empty-icon">&#128202;</div>
            <h3>Select a subject to begin</h3>
            <p>Choose a subject above to load the student list with their latest SBE results.</p>
        </div>
    <?php endif; ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var deptSelect = document.getElementById('department_id');
    var courseSelect = document.getElementById('course_id');
    var sectionSelect = document.getElementById('section_id');

    function filterByDept() {
        var dept = deptSelect ? deptSelect.value : '';
        if (courseSelect) {
            for (var i = 0; i < courseSelect.options.length; i++) {
                var opt = courseSelect.options[i];
                if (!opt.value) continue;
                opt.hidden = dept !== '' && opt.dataset.dept !== dept;
            }
        }
        if (sectionSelect) {
            for (var j = 0; j < sectionSelect.options.length; j++) {
                var sopt = sectionSelect.options[j];
                if (!sopt.value) continue;
                sopt.hidden = dept !== '' && sopt.dataset.dept !== dept;
            }
        }
    }

    if (deptSelect) {
        deptSelect.addEventListener('change', filterByDept);
    }
    filterByDept();
});
</script>

<?php require __DIR__ . '/includes/footer.php'; ?>
