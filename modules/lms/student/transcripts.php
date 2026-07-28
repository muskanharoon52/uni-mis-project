<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';

$user = require_role('student');
$active = 'transcripts';
$pageTitle = 'Academic Transcript';

// Get student_id
$studentStmt = db()->prepare('SELECT student_id FROM students WHERE user_id = ? LIMIT 1');
$studentStmt->execute([(int) $user['id']]);
$studentId = (int) ($studentStmt->fetchColumn() ?: 0);

// Grade point mapping
$gradePoints = ['A+' => 4.0, 'A' => 4.0, 'A-' => 3.7, 'B+' => 3.3, 'B' => 3.0, 'B-' => 2.7, 'C+' => 2.3, 'C' => 2.0, 'C-' => 1.7, 'D' => 1.0, 'F' => 0.0];

$semesterGrades = [];
$allResults = [];

if ($studentId > 0) {
    // Get all published results grouped by exam type and course
    $stmt = db()->prepare(
        'SELECT er.*, es.exam_type, es.date AS exam_date, es.course_id,
                c.course_code, c.course_title
         FROM exam_results er
         JOIN exam_schedules es ON es.exam_id = er.exam_id
         JOIN courses c ON c.course_id = es.course_id
         WHERE er.student_id = ? AND er.status = \'published\'
         ORDER BY es.date, c.course_code'
    );
    $stmt->execute([$studentId]);
    $allResults = $stmt->fetchAll();

    // Group by course and compute GPA
    $courseGrades = [];
    foreach ($allResults as $r) {
        $cid = (int) $r['course_id'];
        if (!isset($courseGrades[$cid])) {
            $courseGrades[$cid] = [
                'course_code' => $r['course_code'],
                'course_title' => $r['course_title'],
                'grades' => [],
            ];
        }
        $courseGrades[$cid]['grades'][] = [
            'exam_type' => $r['exam_type'],
            'marks' => (float) $r['marks_obtained'],
            'total' => (float) $r['total_marks'],
            'grade' => $r['grade'] ?? '-',
            'date' => $r['exam_date'],
        ];
    }

    // Compute final grade per course (use Final exam if exists, else average)
    foreach ($courseGrades as $cid => &$cg) {
        $finalGrade = null;
        foreach ($cg['grades'] as $g) {
            if ($g['exam_type'] === 'Final') {
                $finalGrade = $g;
                break;
            }
        }
        if (!$finalGrade && !empty($cg['grades'])) {
            // Average all grades
            $totalMarks = 0;
            $totalObtained = 0;
            foreach ($cg['grades'] as $g) {
                $totalObtained += $g['marks'];
                $totalMarks += $g['total'];
            }
            $pct = $totalMarks > 0 ? ($totalObtained / $totalMarks) * 100 : 0;
            $grade = 'F';
            if ($pct >= 90) $grade = 'A';
            elseif ($pct >= 80) $grade = 'B';
            elseif ($pct >= 70) $grade = 'C';
            elseif ($pct >= 60) $grade = 'D';
            $finalGrade = ['exam_type' => 'Average', 'marks' => $totalObtained, 'total' => $totalMarks, 'grade' => $grade, 'date' => end($cg['grades'])['date']];
        }
        $cg['final'] = $finalGrade;
        $cg['gpa'] = $gradePoints[$finalGrade['grade']] ?? 0.0;
    }
    unset($cg);

    // Compute overall GPA
    $totalCredits = 0;
    $totalGradePoints = 0;
    foreach ($courseGrades as $cg) {
        if (isset($cg['final'])) {
            $totalCredits++;
            $totalGradePoints += $cg['gpa'];
        }
    }
    $overallGPA = $totalCredits > 0 ? round($totalGradePoints / $totalCredits, 2) : 0.0;
}

require_once __DIR__ . '/../includes/header.php';
?>
<div class="card">
    <div class="card-header"><h3><?= e($pageTitle) ?></h3></div>
    <?php if (empty($courseGrades)): ?>
        <p class="muted" style="padding: 20px;">No published examination results found.</p>
    <?php else: ?>
        <div style="padding: 20px;">
            <div style="display: flex; gap: 40px; margin-bottom: 20px;">
                <div><strong>Overall GPA:</strong> <?= e(number_format($overallGPA, 2)) ?></div>
                <div><strong>Courses Completed:</strong> <?= $totalCredits ?></div>
                <div><strong>Student:</strong> <?= e($user['name']) ?></div>
            </div>
        </div>
        <div class="table-responsive">
        <table>
            <tr>
                <th>Course Code</th>
                <th>Course Title</th>
                <th>Exam Type</th>
                <th>Marks Obtained</th>
                <th>Total Marks</th>
                <th>Percentage</th>
                <th>Grade</th>
                <th>Grade Points</th>
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
                        <td><?= e(number_format($g['total'] > 0 ? ($g['marks'] / $g['total']) * 100 : 0, 1)) ?>%</td>
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
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
