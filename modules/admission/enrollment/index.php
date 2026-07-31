<?php
if (session_status() === PHP_SESSION_NONE) session_start();
$pageTitle = 'Approved Student Enrollment';

$paths = [
    __DIR__ . '/../includes/header.php',
    __DIR__ . '/../../includes/header.php',
    __DIR__ . '/includes/header.php',
];
foreach ($paths as $p) { if (file_exists($p)) { include $p; break; } }

$db_paths = [
    __DIR__ . '/../config/database.php',
    __DIR__ . '/../../config/database.php',
    __DIR__ . '/config/database.php',
];
foreach ($db_paths as $p) { if (file_exists($p)) { require_once $p; $db_found = true; break; } }
if (!isset($db_found)) { die("Config not found"); }

$error = '';
$success = '';

// =============================================
// GET DEPARTMENTS
// =============================================
$departments = $pdo->query("SELECT * FROM departments WHERE status = 'Active' ORDER BY department_name")->fetchAll();

// =============================================
// GET SESSIONS
// =============================================
$sessions = [];
try {
    $session_cols = $pdo->query("SHOW COLUMNS FROM sessions")->fetchAll(PDO::FETCH_COLUMN);
    
    $session_select = "session_id as id, session_name";
    if (in_array('session_code', $session_cols)) {
        $session_select .= ", session_code";
    }
    if (in_array('start_date', $session_cols)) {
        $session_select .= ", start_date";
    }
    if (in_array('end_date', $session_cols)) {
        $session_select .= ", end_date";
    }
    if (in_array('status', $session_cols)) {
        $session_select .= ", status";
    }
    
    $session_stmt = $pdo->query("SELECT $session_select FROM sessions WHERE status = 'Active' ORDER BY session_name");
    $sessions = $session_stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Session error: " . $e->getMessage());
    $sessions = [];
}

// =============================================
// GET SELECTED VALUES
// =============================================
$selected_dept = isset($_GET['dept_id']) ? intval($_GET['dept_id']) : (isset($_POST['dept_id']) ? intval($_POST['dept_id']) : 0);
$selected_session = isset($_GET['session_id']) ? intval($_GET['session_id']) : (isset($_POST['session_id']) ? intval($_POST['session_id']) : 0);
$selected_section = isset($_GET['section_id']) ? intval($_GET['section_id']) : (isset($_POST['section_id']) ? intval($_POST['section_id']) : 0);

// =============================================
// GET SECTIONS
// =============================================
$sections = [];

if ($selected_dept > 0) {
    try {
        $section_sql = "
            SELECT 
                s.*,
                p.program_name,
                p.department_id,
                sem.semester_name
            FROM sections s
            LEFT JOIN programs p ON p.program_id = s.program_id
            LEFT JOIN semesters sem ON sem.semester_id = s.semester_id
            WHERE p.department_id = ? 
            AND s.status = 'Active'
            ORDER BY p.program_name, sem.semester_id, s.section_name
        ";
        
        $section_stmt = $pdo->prepare($section_sql);
        $section_stmt->execute([$selected_dept]);
        $sections = $section_stmt->fetchAll();
        
    } catch (PDOException $e) {
        error_log("Section query error: " . $e->getMessage());
        $sections = [];
    }
}

// =============================================
// STUDENTS AND COURSES
// =============================================
$students = [];
$available_courses = [];
$section_info = null;

if ($selected_section > 0 && !empty($sections)) {
    try {
        // Find selected section info
        foreach ($sections as $sec) {
            if ($sec['section_id'] == $selected_section) {
                $section_info = $sec;
                break;
            }
        }
        
        if ($section_info) {
            $prog_id = $section_info['program_id'] ?? 0;
            $sem_id = $section_info['semester_id'] ?? 0;
            
            if ($prog_id > 0) {
                // Get students with fee paid
                $stu_stmt = $pdo->prepare("
                    SELECT asd.*, aa.full_name as app_full_name, aa.father_name, aa.cnic_or_bform,
                           aa.dob, aa.gender, aa.contact_no, aa.email, aa.address, aa.applied_semester_id,
                           p.program_name
                    FROM admission_students asd
                    JOIN admission_applications aa ON aa.application_id = asd.application_id
                    JOIN programs p ON p.program_id = asd.program_id
                    WHERE asd.fee_paid = 1
                    AND asd.program_id = ?
                    AND asd.application_id NOT IN (
                        SELECT application_id FROM students WHERE application_id IS NOT NULL
                    )
                    ORDER BY asd.full_name
                ");
                $stu_stmt->execute([$prog_id]);
                $students = $stu_stmt->fetchAll();
                
                // Get available courses
                if ($sem_id > 0) {
                    $course_stmt = $pdo->prepare("
                        SELECT * FROM courses
                        WHERE program_id = ? AND semester_id = ? AND status = 'Active'
                        ORDER BY course_code
                    ");
                    $course_stmt->execute([$prog_id, $sem_id]);
                    $available_courses = $course_stmt->fetchAll();
                }
            }
        }
    } catch (PDOException $e) {
        error_log("Student/Course query error: " . $e->getMessage());
    }
}

// =============================================
// HANDLE FORM SUBMISSION
// =============================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register_selected'])) {
    $student_ids = isset($_POST['student_ids']) ? $_POST['student_ids'] : [];
    $course_ids = isset($_POST['course_ids']) ? $_POST['course_ids'] : [];

    if (empty($student_ids)) { 
        $error = "No students selected."; 
    } elseif (empty($course_ids)) { 
        $error = "No courses selected."; 
    } else {
        $registered = 0;
        $errors = [];
        $pdo->beginTransaction();
        try {
            // Get next login ID
            $login_result = $pdo->query("SELECT MAX(CAST(login_id AS UNSIGNED)) as max_login FROM users");
            $login_row = $login_result->fetch();
            $next_login = ($login_row['max_login'] ?? 9000) + 1;

            // Get next student ID
            $sid_result = $pdo->query("SELECT MAX(student_id) as max_sid FROM students");
            $sid_row = $sid_result->fetch();
            $next_sid = ($sid_row['max_sid'] ?? 0) + 1;

            foreach ($student_ids as $adm_id) {
                $adm_id = intval($adm_id);
                $adm_stmt = $pdo->prepare("
                    SELECT asd.*, aa.full_name, aa.father_name, aa.cnic_or_bform,
                           aa.dob, aa.gender, aa.contact_no, aa.email, aa.address,
                           aa.program_id, aa.applied_semester_id
                    FROM admission_students asd
                    JOIN admission_applications aa ON aa.application_id = asd.application_id
                    WHERE asd.id = ? AND asd.fee_paid = 1
                ");
                $adm_stmt->execute([$adm_id]);
                $row = $adm_stmt->fetch();
                
                if (!$row) { 
                    $errors[] = "Student #$adm_id not found or fee not paid."; 
                    continue; 
                }
                
                if ($row['program_id'] != ($section_info['program_id'] ?? 0)) { 
                    $errors[] = "{$row['full_name']} program mismatch."; 
                    continue; 
                }

                $app_id_val = $row['application_id'];
                $login_id = $next_login++;
                $username = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', explode(' ', $row['full_name'])[0])) . $login_id;
                $password_hash = password_hash('student123', PASSWORD_DEFAULT);
                $roll_no = date('Y') . '-' . ($row['program_id'] ?? 1) . '-' . str_pad($next_sid++, 3, '0', STR_PAD_LEFT);

                // Insert user
                $user_stmt = $pdo->prepare("
                    INSERT INTO users (full_name, username, login_id, email, phone, password_hash, role_id, department_id, status) 
                    VALUES (?, ?, ?, ?, ?, ?, 4, ?, 'Active')
                ");
                $user_stmt->execute([
                    $row['full_name'], 
                    $username, 
                    $login_id, 
                    $row['email'] ?? $username . '@university.edu', 
                    $row['contact_no'] ?? '', 
                    $password_hash, 
                    $row['program_id'] ?? 1
                ]);
                $new_user_id = (int) $pdo->lastInsertId();

                // Insert student
                $admission_date = date('Y-m-d');
                $student_stmt = $pdo->prepare("
                    INSERT INTO students (
                        application_id, roll_no, full_name, father_name, cnic_or_bform, 
                        dob, gender, contact_no, email, address, program_id, 
                        admission_session_id, current_session_id, current_semester_id, 
                        batch_year, admission_date, status, user_id
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Active', ?)
                ");
                $semester_val = $row['applied_semester_id'] ?? 1;
                $student_stmt->execute([
                    $app_id_val, 
                    $roll_no, 
                    $row['full_name'], 
                    $row['father_name'] ?? '', 
                    $row['cnic_or_bform'] ?? '', 
                    $row['dob'] ?? null, 
                    $row['gender'] ?? 'Male', 
                    $row['contact_no'] ?? '', 
                    $row['email'] ?? '', 
                    $row['address'] ?? '', 
                    $row['program_id'] ?? 1, 
                    $selected_session ?: 1, 
                    $selected_session ?: 1, 
                    $semester_val, 
                    (int)date('Y'), 
                    $admission_date, 
                    $new_user_id
                ]);

                // Update student record with section_id
                $update_section_stmt = $pdo->prepare("
                    UPDATE students SET section_id = ? WHERE application_id = ?
                ");
                $update_section_stmt->execute([$selected_section, $app_id_val]);

                // Enroll in courses
                foreach ($course_ids as $cid) {
                    $cid = intval($cid);
                    $crs = $pdo->prepare("SELECT course_code, course_name, credit_hours FROM courses WHERE course_id = ?");
                    $crs->execute([$cid]);
                    $c = $crs->fetch();
                    if ($c) {
                        $pdo->prepare("
                            INSERT INTO student_courses (student_id, course_id, enrollment_date, status) 
                            VALUES (?, ?, CURDATE(), 'Active')
                        ")->execute([$roll_no, $cid]);
                        
                        $pdo->prepare("
                            INSERT INTO student_course_allocation (application_id, course_id, course_code, course_name, credit_hours, semester, allocated_by) 
                            VALUES (?, ?, ?, ?, ?, ?, ?)
                        ")->execute([
                            $app_id_val, 
                            $cid, 
                            $c['course_code'], 
                            $c['course_name'], 
                            $c['credit_hours'], 
                            $semester_val, 
                            $_SESSION['user_id'] ?? 1
                        ]);
                    }
                }

                // Update application status
                $pdo->prepare("
                    UPDATE admission_applications SET application_status = 'Admitted', status = 'admitted' 
                    WHERE application_id = ?
                ")->execute([$app_id_val]);
                
                $registered++;
            }
            $pdo->commit();
            $success = "$registered student(s) registered and enrolled successfully.";
            if (!empty($errors)) $success .= '<br>' . implode('<br>', array_map('htmlspecialchars', $errors));
            
            // Refresh data
            $students = [];
            $section_info = null;
            
        } catch (Exception $e) {
            $pdo->rollBack();
            $error = "Error: " . $e->getMessage();
        }
    }
}
?>

<?php if ($error): ?>
    <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<?php if ($success): ?>
    <div class="alert alert-success"><?= $success ?></div>
<?php endif; ?>

<div class="card">
    <div class="card-header">
        <h3>Approved Student Enrollment</h3>
        <p>Search fee-paid students by department, session, and section to assign courses and complete registration.</p>
    </div>
    <form method="GET" style="padding:18px 22px;">
        <div class="inline-form-row" style="grid-template-columns:1fr 1fr 1fr;">
            <div class="field" style="margin-bottom:0;">
                <label>Department <span style="color:var(--danger);">*</span></label>
                <select name="dept_id" required onchange="this.form.submit()">
                    <option value="">Select Department</option>
                    <?php foreach ($departments as $d): ?>
                        <option value="<?= $d['department_id'] ?>" <?= $selected_dept == $d['department_id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($d['department_name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="field" style="margin-bottom:0;">
                <label>Session</label>
                <select name="session_id" onchange="this.form.submit()">
                    <option value="">Select Session</option>
                    <?php foreach ($sessions as $s): ?>
                        <?php 
                        $display = htmlspecialchars($s['session_name']);
                        if (isset($s['session_code']) && !empty($s['session_code'])) {
                            $display .= ' (' . htmlspecialchars($s['session_code']) . ')';
                        }
                        ?>
                        <option value="<?= $s['id'] ?>" <?= $selected_session == $s['id'] ? 'selected' : '' ?>>
                            <?= $display ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="field" style="margin-bottom:0;">
                <label>Section <span style="color:var(--danger);">*</span></label>
                <select name="section_id" required onchange="this.form.submit()">
                    <option value="">Select Section</option>
                    <?php if (!empty($sections)): ?>
                        <?php foreach ($sections as $sec): ?>
                            <?php 
                            $section_display = htmlspecialchars($sec['section_name'] ?? '');
                            if (isset($sec['program_name']) && !empty($sec['program_name'])) {
                                $section_display .= ' - ' . htmlspecialchars($sec['program_name']);
                            }
                            if (isset($sec['semester_name']) && !empty($sec['semester_name'])) {
                                $section_display .= ' (' . htmlspecialchars($sec['semester_name']) . ')';
                            }
                            if (isset($sec['capacity']) && !empty($sec['capacity'])) {
                                $section_display .= ' [Capacity: ' . $sec['capacity'] . ']';
                            }
                            ?>
                            <option value="<?= $sec['section_id'] ?>" <?= $selected_section == $sec['section_id'] ? 'selected' : '' ?>>
                                <?= $section_display ?>
                            </option>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <option value="" disabled>No sections found for this department</option>
                    <?php endif; ?>
                </select>
                <?php if ($selected_dept > 0 && empty($sections)): ?>
                    <div style="font-size:12px;color:#dc2626;margin-top:4px;background:#fee2e2;padding:8px;border-radius:4px;">
                        ⚠️ <strong>No sections found.</strong> Please add sections in the database.
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <?php if ($selected_dept || $selected_session || $selected_section): ?>
            <div style="margin-top:12px;">
                <a href="index.php" class="btn btn-ghost btn-sm">Clear Filters</a>
            </div>
        <?php endif; ?>
    </form>
</div>

<?php if ($section_info): ?>
<form method="POST">
    <input type="hidden" name="dept_id" value="<?= $selected_dept ?>">
    <input type="hidden" name="session_id" value="<?= $selected_session ?>">
    <input type="hidden" name="section_id" value="<?= $selected_section ?>">

    <?php if (!empty($students)): ?>
    <div class="card" style="margin-top:18px;">
        <div class="card-header">
            <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:10px;">
                <h3>Students (<?= count($students) ?> found)</h3>
                <label style="font-size:13px;"><input type="checkbox" id="select_all_students" checked> Select All</label>
            </div>
            <p style="margin:2px 0 0 0;font-size:13px;color:#6b7280;">
                Section: <?= htmlspecialchars($section_info['section_name'] ?? '') ?> | 
                Program: <?= htmlspecialchars($section_info['program_name'] ?? '') ?> | 
                Semester: <?= htmlspecialchars($section_info['semester_name'] ?? '') ?>
            </p>
        </div>
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th style="width:40px;">Select</th>
                        <th>Application ID</th>
                        <th>Name</th>
                        <th>Father Name</th>
                        <th>Program</th>
                        <th>Contact</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($students as $stu): ?>
                    <tr>
                        <td><input type="checkbox" name="student_ids[]" value="<?= $stu['id'] ?>" class="student-cb" checked></td>
                        <td style="font-weight:600;"><?= htmlspecialchars($stu['application_id']) ?></td>
                        <td><?= htmlspecialchars($stu['full_name']) ?></td>
                        <td><?= htmlspecialchars($stu['father_name'] ?? 'N/A') ?></td>
                        <td><?= htmlspecialchars($stu['program_name'] ?? 'N/A') ?></td>
                        <td class="muted"><?= htmlspecialchars($stu['contact_no'] ?? 'N/A') ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>

    <?php if (!empty($available_courses)): ?>
    <div class="card" style="margin-top:18px;">
        <div class="card-header">
            <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:10px;">
                <h3>Available Courses (<?= count($available_courses) ?>)</h3>
                <label style="font-size:13px;"><input type="checkbox" id="select_all_courses" checked> Select All</label>
            </div>
            <p style="margin:2px 0 0 0;font-size:13px;color:#6b7280;">
                Program: <?= htmlspecialchars($section_info['program_name'] ?? '') ?> | 
                Semester: <?= htmlspecialchars($section_info['semester_name'] ?? '') ?>
            </p>
        </div>
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th style="width:40px;">Select</th>
                        <th>Code</th>
                        <th>Course Name</th>
                        <th>Credit Hours</th>
                        <th>Type</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($available_courses as $c): ?>
                    <tr>
                        <td><input type="checkbox" name="course_ids[]" value="<?= $c['course_id'] ?>" class="course-cb" checked></td>
                        <td style="font-weight:600;"><?= htmlspecialchars($c['course_code']) ?></td>
                        <td><?= htmlspecialchars($c['course_title'] ?: $c['course_name']) ?></td>
                        <td><?= $c['credit_hours'] ?></td>
                        <td><?= htmlspecialchars($c['course_type'] ?? 'Core') ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>

    <?php if (empty($students) && empty($available_courses)): ?>
    <div class="card" style="margin-top:18px;">
        <div class="empty-state" style="padding:40px;text-align:center;color:var(--muted);">
            <?php if (empty($students) && !empty($available_courses)): ?>
                <p>No fee-paid students found for this section.</p>
            <?php elseif (!empty($students) && empty($available_courses)): ?>
                <p>No courses available for this program and semester.</p>
            <?php else: ?>
                <p>No fee-paid students or courses found for this section.</p>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>

    <?php if (!empty($students) && !empty($available_courses)): ?>
    <div style="margin-top:18px;padding:16px 0;display:flex;align-items:center;gap:15px;flex-wrap:wrap;">
        <button type="submit" name="register_selected" class="btn btn-primary" onclick="return confirm('Register selected students and enroll them in selected courses?\n\nThis will:\n- Create user accounts\n- Generate roll numbers\n- Enroll in selected courses\n- Update application status to Admitted');">
            <i class="fas fa-user-plus"></i> Register &amp; Enroll Selected
        </button>
        <span class="muted">
            <span id="stu_count"><?= count($students) ?></span> students, 
            <span id="crs_count"><?= count($available_courses) ?></span> courses
        </span>
    </div>
    <?php endif; ?>
</form>

<script>
document.getElementById('select_all_students')?.addEventListener('change', function() {
    document.querySelectorAll('.student-cb').forEach(cb => cb.checked = this.checked);
    updateCounts();
});

document.getElementById('select_all_courses')?.addEventListener('change', function() {
    document.querySelectorAll('.course-cb').forEach(cb => cb.checked = this.checked);
    updateCounts();
});

document.querySelectorAll('.student-cb').forEach(cb => cb.addEventListener('change', updateCounts));
document.querySelectorAll('.course-cb').forEach(cb => cb.addEventListener('change', updateCounts));

function updateCounts() {
    const stuCount = document.querySelectorAll('.student-cb:checked').length;
    const crsCount = document.querySelectorAll('.course-cb:checked').length;
    document.getElementById('stu_count').textContent = stuCount;
    document.getElementById('crs_count').textContent = crsCount;
}
</script>

<?php elseif ($selected_dept > 0): ?>
<div class="card" style="margin-top:18px;">
    <div class="empty-state" style="padding:40px;text-align:center;color:var(--muted);">
        <p>Select a section to view students and courses.</p>
        <?php if (!empty($sections)): ?>
            <p style="font-size:13px;">Available sections: <?= count($sections) ?></p>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>

<?php
$footer_paths = [
    __DIR__ . '/../includes/footer.php',
    __DIR__ . '/../../includes/footer.php',
    __DIR__ . '/includes/footer.php',
];
foreach ($footer_paths as $p) { if (file_exists($p)) { include $p; break; } }
?>