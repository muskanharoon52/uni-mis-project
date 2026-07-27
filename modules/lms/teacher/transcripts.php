<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';

$user = require_role('teacher');
$active = 'transcripts';
$pageTitle = 'Student Transcripts';

$teacherId = (int) $user['teacher_id'];

// Get students enrolled in this teacher's courses who have published exam results
$studentData = [];
if ($teacherId > 0) {
    $stmt = db()->prepare(
        'SELECT DISTINCT s.student_id, s.full_name, s.roll_no, u.user_id
         FROM exam_results er
         JOIN exam_schedules es ON es.exam_id = er.exam_id
         JOIN courses c ON c.course_id = es.course_id
         JOIN students s ON s.student_id = er.student_id
         JOIN users u ON u.user_id = s.user_id
         WHERE c.teacher_id = ? AND er.status = \'published\'
         ORDER BY s.full_name'
    );
    $stmt->execute([$teacherId]);
    $studentData = $stmt->fetchAll();
}

// Grade point mapping
$gradePoints = ['A+' => 4.0, 'A' => 4.0, 'A-' => 3.7, 'B+' => 3.3, 'B' => 3.0, 'B-' => 2.7, 'C+' => 2.3, 'C' => 2.0, 'C-' => 1.7, 'D' => 1.0, 'F' => 0.0];

// If a student is selected, show their transcript
$selectedStudent = null;
$studentResults = [];
$overallGPA = 0.0;

if (isset($_GET['student_id']) && $teacherId > 0) {
    $selId = (int) $_GET['student_id'];
    $selStmt = db()->prepare(
        'SELECT s.student_id, s.full_name, s.roll_no, u.user_id
         FROM students s JOIN users u ON u.user_id = s.user_id
         WHERE s.student_id = ? LIMIT 1'
    );
    $selStmt->execute([$selId]);
    $selectedStudent = $selStmt->fetch();

    if ($selectedStudent) {
        $stmt = db()->prepare(
            'SELECT er.*, es.exam_type, es.date AS exam_date, es.course_id,
                    c.course_code, c.course_title
             FROM exam_results er
             JOIN exam_schedules es ON es.exam_id = er.exam_id
             JOIN courses c ON c.course_id = es.course_id
             WHERE er.student_id = ? AND c.teacher_id = ? AND er.status = \'published\'
             ORDER BY es.date, c.course_code'
        );
        $stmt->execute([$selId, $teacherId]);
        $studentResults = $stmt->fetchAll();

        // Group by course
        $courseGrades = [];
        foreach ($studentResults as $r) {
            $cid = (int) $r['course_id'];
            if (!isset($courseGrades[$cid])) {
                $courseGrades[$cid] = ['course_code' => $r['course_code'], 'course_title' => $r['course_title'], 'grades' => []];
            }
            $courseGrades[$cid]['grades'][] = [
                'exam_type' => $r['exam_type'], 'marks' => (float) $r['marks_obtained'],
                'total' => (float) $r['total_marks'], 'grade' => $r['grade'] ?? '-', 'date' => $r['exam_date'],
            ];
        }

        foreach ($courseGrades as &$cg) {
            $finalGrade = null;
            foreach ($cg['grades'] as $g) {
                if ($g['exam_type'] === 'Final') { $finalGrade = $g; break; }
            }
            if (!$finalGrade && !empty($cg['grades'])) {
                $tM = 0; $tO = 0;
                foreach ($cg['grades'] as $g) { $tO += $g['marks']; $tM += $g['total']; }
                $pct = $tM > 0 ? ($tO / $tM) * 100 : 0;
                $grade = 'F';
                if ($pct >= 90) $grade = 'A'; elseif ($pct >= 80) $grade = 'B'; elseif ($pct >= 70) $grade = 'C'; elseif ($pct >= 60) $grade = 'D';
                $finalGrade = ['exam_type' => 'Average', 'marks' => $tO, 'total' => $tM, 'grade' => $grade, 'date' => end($cg['grades'])['date']];
            }
            $cg['final'] = $finalGrade;
            $cg['gpa'] = $gradePoints[$finalGrade['grade']] ?? 0.0;
        }
        unset($cg);

        $tc = 0; $tgp = 0;
        foreach ($courseGrades as $cg) {
            if (isset($cg['final'])) { $tc++; $tgp += $cg['gpa']; }
        }
        $overallGPA = $tc > 0 ? round($tgp / $tc, 2) : 0.0;
    }
}

require_once __DIR__ . '/../includes/header.php';
?>
<div class="card">
    <div class="card-header"><h3><?= e($pageTitle) ?></h3></div>
    <?php if (!$studentData): ?>
        <p class="muted" style="padding: 20px;">No students with published exam results found for your courses.</p>
    <?php else: ?>
        <div style="padding: 20px;">
            <strong>Select Student:</strong>
            <select onchange="window.location.href='<?= app_url('teacher/transcripts.php') ?>?student_id='+this.value" style="margin-left: 10px; padding: 5px;">
                <option value="">-- Choose --</option>
                <?php foreach ($studentData as $s): ?>
                    <option value="<?= (int) $s['student_id'] ?>" <?= ($selectedStudent && (int) $selectedStudent['student_id'] === (int) $s['student_id']) ? 'selected' : '' ?>>
                        <?= e($s['full_name']) ?> (<?= e($s['roll_no'] ?? 'N/A') ?>)
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <?php if ($selectedStudent): ?>
            <div style="padding: 0 20px 10px;">
                <strong>Student:</strong> <?= e($selectedStudent['full_name']) ?> |
                <strong>Roll No:</strong> <?= e($selectedStudent['roll_no'] ?? 'N/A') ?> |
                <strong>GPA:</strong> <?= e(number_format($overallGPA, 2)) ?>
            </div>

            <?php if (empty($courseGrades)): ?>
                <p class="muted" style="padding: 10px 20px;">No published exam results found for this student in your courses.</p>
            <?php else: ?>
                <div class="table-responsive">
                <table>
                    <tr>
                        <th>Course Code</th>
                        <th>Course Title</th>
                        <th>Exam Type</th>
                        <th>Marks</th>
                        <th>Total</th>
                        <th>Grade</th>
                        <th>GPA</th>
                    </tr>
                    <?php foreach ($courseGrades as $cg): ?>
                        <?php foreach ($cg['grades'] as $i => $g): ?>
                            <tr>
                                <?php if ($i === 0): ?>
                                    <td rowspan="<?= count($cg['grades']) ?>"><?= e($cg['course_code']) ?></td>
                                    <td rowspan="<?= count($cg['grades']) ?>"><?= e($cg['course_title']) ?></td>
                                <?php endif; ?>
                                <td><?= e($g['exam_type']) ?></td>
                                <td><?= e(number_format($g['marks'], 2)) ?></td>
                                <td><?= e(number_format($g['total'], 2)) ?></td>
                                <td><strong><?= e($g['grade']) ?></strong></td>
                                <?php if ($i === 0): ?>
                                    <td rowspan="<?= count($cg['grades']) ?>"><?= e(number_format($cg['gpa'], 1)) ?></td>
                                <?php endif; ?>
                            </tr>
                        <?php endforeach; ?>
                    <?php endforeach; ?>
                </table>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    <?php endif; ?>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
