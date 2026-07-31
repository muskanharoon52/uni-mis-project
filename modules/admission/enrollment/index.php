<?php
if (session_status() === PHP_SESSION_NONE) session_start();
$pageTitle = 'Activate Paid Students';

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

// =============================================
// GET FEE-PAID STUDENTS (ALL OF THEM, NO SECTION FILTER)
// =============================================
$students = [];

if ($selected_dept > 0) {
    try {
        $sql = "
            SELECT asd.*, aa.full_name as app_full_name, aa.father_name, aa.cnic_or_bform,
                   aa.dob, aa.gender, aa.contact_no, aa.email, aa.address, aa.applied_semester_id,
                   p.program_name, d.department_name
            FROM admission_students asd
            JOIN admission_applications aa ON aa.application_id = asd.application_id
            JOIN programs p ON p.program_id = asd.program_id
            JOIN departments d ON d.department_id = p.department_id
            WHERE asd.fee_paid = 1
            AND p.department_id = ?
        ";

        // If a session is selected, filter by it
        if ($selected_session > 0) {
            $sql .= " AND aa.session_id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$selected_dept, $selected_session]);
        } else {
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$selected_dept]);
        }

        $students = $stmt->fetchAll();
        
    } catch (PDOException $e) {
        error_log("Student query error: " . $e->getMessage());
        $students = [];
    }
}

// =============================================
// HANDLE FORM SUBMISSION (ACTIVATE STUDENTS)
// =============================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['activate_selected'])) {
    $student_ids = isset($_POST['student_ids']) ? $_POST['student_ids'] : [];

    if (empty($student_ids)) { 
        $error = "No students selected."; 
    } else {
        $activated = 0;
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
                
                // Fetch student details
                $adm_stmt = $pdo->prepare("
                    SELECT asd.*, COALESCE(NULLIF(aa.full_name, ''), asd.full_name) AS full_name,
                           COALESCE(NULLIF(aa.father_name, ''), asd.father_name) AS father_name,
                           COALESCE(NULLIF(aa.cnic_or_bform, ''), asd.cnic_or_bform) AS cnic_or_bform,
                           COALESCE(NULLIF(aa.dob, ''), asd.dob) AS dob,
                           COALESCE(NULLIF(aa.gender, ''), asd.gender) AS gender,
                           COALESCE(NULLIF(aa.contact_no, ''), asd.contact_no) AS contact_no,
                           COALESCE(NULLIF(aa.email, ''), asd.email) AS email,
                           COALESCE(NULLIF(aa.address, ''), asd.address) AS address,
                           aa.program_id, aa.applied_semester_id, aa.session_id
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
                $session_val = $row['session_id'] ?? 1;
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
                    $session_val, 
                    $session_val, 
                    $semester_val, 
                    (int)date('Y'), 
                    $admission_date, 
                    $new_user_id
                ]);

                // Update application status
                $pdo->prepare("
                    UPDATE admission_applications SET application_status = 'Admitted', status = 'admitted' 
                    WHERE application_id = ?
                ")->execute([$app_id_val]);

                // Mark as processed in admission_students (optional)
                $pdo->prepare("UPDATE admission_students SET is_activated = 1 WHERE id = ?")->execute([$adm_id]);
                
                $activated++;
            }
            $pdo->commit();
            $success = "$activated student(s) activated successfully. Student IDs and User Accounts created.";
            if (!empty($errors)) $success .= '<br>' . implode('<br>', array_map('htmlspecialchars', $errors));
            
            // Refresh data
            $students = [];
            
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
        <h3>Activate Fee-Paid Students</h3>
        <p>Select a department to view students who have paid their fees, then activate them to generate their Student ID and User Account.</p>
    </div>
    <form method="GET" style="padding:18px 22px;">
        <div class="inline-form-row" style="grid-template-columns:1fr 1fr;">
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
                <label>Session (Optional)</label>
                <select name="session_id" onchange="this.form.submit()">
                    <option value="">Select Session</option>
                    <?php foreach ($sessions as $s): ?>
                        <option value="<?= $s['id'] ?>" <?= $selected_session == $s['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($s['session_name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <?php if ($selected_dept || $selected_session): ?>
            <div style="margin-top:12px;">
                <a href="index.php" class="btn btn-ghost btn-sm">Clear Filters</a>
            </div>
        <?php endif; ?>
    </form>
</div>

<?php if ($selected_dept > 0): ?>
<form method="POST">
    <div class="card" style="margin-top:18px;">
        <div class="card-header">
            <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:10px;">
                <h3>Fee-Paid Students (<?= count($students) ?> found)</h3>
                <label style="font-size:13px;"><input type="checkbox" id="select_all_students" checked> Select All</label>
            </div>
        </div>
        <?php if (!empty($students)): ?>
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th style="width:40px;">Select</th>
                        <th>Application ID</th>
                        <th>Name</th>
                        <th>Father Name</th>
                        <th>Program</th>
                        <th>Session</th>
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
                        <td class="muted"><?= htmlspecialchars($stu['session_id'] ?? 'N/A') ?></td>
                        <td class="muted"><?= htmlspecialchars($stu['contact_no'] ?? 'N/A') ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <div style="padding:16px;border-top:1px solid var(--border);display:flex;align-items:center;gap:15px;flex-wrap:wrap;">
            <button type="submit" name="activate_selected" class="btn btn-primary" onclick="return confirm('Activate selected students?\n\nThis will:\n- Create user accounts\n- Generate roll numbers\n- Update application status to Admitted');">
                <i class="fas fa-user-plus"></i> Activate Selected &amp; Create Student ID
            </button>
            <span class="muted">
                <span id="stu_count"><?= count($students) ?></span> students selected
            </span>
        </div>
        <?php else: ?>
        <div class="empty-state" style="padding:40px;text-align:center;color:var(--muted);">
            <p>No fee-paid students found for this department.</p>
        </div>
        <?php endif; ?>
    </div>
</form>

<script>
document.getElementById('select_all_students')?.addEventListener('change', function() {
    document.querySelectorAll('.student-cb').forEach(cb => cb.checked = this.checked);
    updateCounts();
});

document.querySelectorAll('.student-cb').forEach(cb => cb.addEventListener('change', updateCounts));

function updateCounts() {
    const stuCount = document.querySelectorAll('.student-cb:checked').length;
    document.getElementById('stu_count').textContent = stuCount;
}
</script>

<?php else: ?>
<div class="card" style="margin-top:18px;">
    <div class="empty-state" style="padding:40px;text-align:center;color:var(--muted);">
        <p>Please select a department to view fee-paid students.</p>
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