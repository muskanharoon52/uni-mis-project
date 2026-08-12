<?php
// notifications/_helpers.php - Shared recipient logic for the SSO Notifications module.

if (!function_exists('notif_departments')) {
    function notif_departments($conn) {
        $out = [];
        $res = mysqli_query($conn, "SELECT department_id, department_name FROM departments WHERE status = 'Active' ORDER BY department_name");
        if ($res) { while ($r = mysqli_fetch_assoc($res)) { $out[] = $r; } }
        return $out;
    }
}

if (!function_exists('notif_batches')) {
    function notif_batches($conn) {
        $out = [];
        $res = mysqli_query($conn, "SELECT DISTINCT batch_year FROM students WHERE status = 'Active' AND batch_year IS NOT NULL ORDER BY batch_year DESC");
        if ($res) { while ($r = mysqli_fetch_assoc($res)) { $out[] = (int)$r['batch_year']; } }
        return $out;
    }
}

if (!function_exists('notif_faculty_ids')) {
    // Faculty = teachers. Filters: by department (AND) and/or by batch (AND).
    // "By batch" = teachers whose assigned courses are taken by students of that batch year.
    function notif_faculty_ids($conn, $deptIds = [], $batchYears = []) {
        $sql = "SELECT t.teacher_id FROM teachers t WHERE t.status = 'Active'";
        $cond = [];
        if (!empty($deptIds)) {
            $deptIds = array_map('intval', (array)$deptIds);
            $cond[] = "t.department_id IN (" . implode(',', $deptIds) . ")";
        }
        if (!empty($batchYears)) {
            $years = array_map('intval', (array)$batchYears);
            $cond[] = "t.teacher_id IN (
                SELECT DISTINCT c.teacher_id FROM courses c
                JOIN student_courses sc ON sc.course_id = c.course_id
                JOIN students s ON s.student_id = sc.student_id
                WHERE c.teacher_id IS NOT NULL AND s.status = 'Active'
                  AND s.batch_year IN (" . implode(',', $years) . "))";
        }
        if (!empty($cond)) { $sql .= ' AND ' . implode(' AND ', $cond); }
        $ids = [];
        $res = mysqli_query($conn, $sql);
        if ($res) { while ($r = mysqli_fetch_assoc($res)) { $ids[] = (int)$r['teacher_id']; } }
        return $ids;
    }
}

if (!function_exists('notif_student_ids')) {
    // Students = enrolled (Active) students from the students table.
    function notif_student_ids($conn, $deptIds = [], $batchYears = []) {
        $sql = "SELECT s.student_id FROM students s
                LEFT JOIN programs p ON p.program_id = s.program_id
                WHERE s.status = 'Active'";
        $cond = [];
        if (!empty($deptIds)) {
            $deptIds = array_map('intval', (array)$deptIds);
            $cond[] = "p.department_id IN (" . implode(',', $deptIds) . ")";
        }
        if (!empty($batchYears)) {
            $years = array_map('intval', (array)$batchYears);
            $cond[] = "s.batch_year IN (" . implode(',', $years) . ")";
        }
        if (!empty($cond)) { $sql .= ' AND ' . implode(' AND ', $cond); }
        $ids = [];
        $res = mysqli_query($conn, $sql);
        if ($res) { while ($r = mysqli_fetch_assoc($res)) { $ids[] = (int)$r['student_id']; } }
        return $ids;
    }
}

if (!function_exists('notif_compute')) {
    // Returns ['faculty' => [ids], 'students' => [ids], 'faculty_count', 'student_count', 'total']
    function notif_compute($conn, $audience, $fScope, $fDepts, $fBatches, $sScope, $sDepts, $sBatches) {
        $faculty = [];
        $students = [];
        $wantFaculty = in_array($audience, ['Faculty', 'Both'], true);
        $wantStudents = in_array($audience, ['Students', 'Both'], true);

        if ($wantFaculty) {
            $faculty = ($fScope === 'All') ? notif_faculty_ids($conn) : notif_faculty_ids($conn, $fDepts, $fBatches);
        }
        if ($wantStudents) {
            $students = ($sScope === 'All') ? notif_student_ids($conn) : notif_student_ids($conn, $sDepts, $sBatches);
        }
        return [
            'faculty' => $faculty,
            'students' => $students,
            'faculty_count' => count($faculty),
            'student_count' => count($students),
            'total' => count($faculty) + count($students),
        ];
    }
}
