<?php
require_once __DIR__ . '/../config/database.php';
$page_title = 'Review Application';
include __DIR__ . '/../includes/header.php';

$id = $_GET['id'] ?? 0;

$stmt = $pdo->prepare("
    SELECT a.*, d.department_name 
    FROM admission_applications a 
    LEFT JOIN departments d ON a.program_id = d.department_id 
    WHERE a.application_id = ?
");
$stmt->execute([$id]);
$app = $stmt->fetch();

if (!$app || !in_array($app['application_status'] ?? '', ['Submitted', 'Under Review'])) {
    setFlash('error', 'Application cannot be reviewed');
    header('Location: index.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $status = $_POST['status'];
        $rejection_reason = sanitize($_POST['rejection_reason'] ?? '');
        
        // Start transaction
        $pdo->beginTransaction();
        
        // Update application status
        $stmt = $pdo->prepare("
            UPDATE admission_applications SET 
                application_status = ?,
                reviewed_by = ?,
                reviewed_at = NOW(),
                rejection_reason = ?
            WHERE application_id = ?
        ");
        
        if ($stmt->execute([$status, $_SESSION['user_id'], $rejection_reason, $id])) {
            
            // =============================================
            // AUTO CREATE STUDENT IF APPROVED OR ADMITTED
            // =============================================
            if (in_array($status, ['Approved', 'Admitted'])) {
                
                // Check if student already exists for this application
                $check = $pdo->prepare("SELECT id FROM admission_students WHERE application_id = ?");
                $check->execute([$id]);
                $existing = $check->fetch();
                
                if (!$existing) {
                    // Generate student ID with new format
                    $student_id = generateStudentId();

                    // --- 1. Create entry in `users` table (for SSO authentication) ---
                    $default_password = 'student123';
                    $password_hash = password_hash($default_password, PASSWORD_DEFAULT);
                    $username = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', explode(' ', $app['full_name'])[0])) . rand(100, 999);
                    $login_id = strval(9000 + (int)date('Y') % 1000 + rand(100, 999));
                    // Ensure login_id is unique
                    while (true) {
                        $check_uid = $pdo->prepare("SELECT user_id FROM users WHERE login_id = ?");
                        $check_uid->execute([$login_id]);
                        if (!$check_uid->fetch()) break;
                        $login_id = strval(9000 + (int)date('Y') % 1000 + rand(100, 999));
                    }

                    $user_stmt = $pdo->prepare("
                        INSERT INTO users (full_name, username, login_id, email, phone, password_hash, role_id, department_id, status)
                        VALUES (?, ?, ?, ?, ?, ?, 4, ?, 'Active')
                    ");
                    $user_result = $user_stmt->execute([
                        $app['full_name'],
                        $username,
                        $login_id,
                        $app['email'] ?? $username . '@university.edu',
                        $app['contact_no'] ?? null,
                        $password_hash,
                        $app['program_id'] ?? 1
                    ]);
                    $new_user_id = (int) $pdo->lastInsertId();

                    // --- 2. Create entry in `admission_students` table (admission module) ---
                    $student_stmt = $pdo->prepare("
                        INSERT INTO admission_students 
                        (student_id, application_id, student_name, father_name, cnic_or_bform, 
                         dob, gender, contact_no, email, address, program_id, status) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'active')
                    ");
                    $student_result = $student_stmt->execute([
                        $student_id,
                        $app['application_id'],
                        $app['full_name'],
                        $app['father_name'],
                        $app['cnic_or_bform'],
                        $app['dob'],
                        $app['gender'],
                        $app['contact_no'],
                        $app['email'],
                        $app['address'],
                        $app['program_id']
                    ]);

                    // --- 3. Create entry in `students` table (main table for examination/finance/LMS) ---
                    $roll_no = strtoupper(substr(explode(' ', $app['full_name'])[0], 0, 3)) . '-' . date('Y') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
                    $main_student_stmt = $pdo->prepare("
                        INSERT INTO students 
                        (application_id, roll_no, full_name, father_name, cnic_or_bform, 
                         dob, gender, contact_no, email, address, program_id, 
                         admission_session_id, current_session_id, current_semester_id, 
                         batch_year, admission_date, status)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Active')
                    ");
                    $main_student_result = $main_student_stmt->execute([
                        $app['application_id'],
                        $roll_no,
                        $app['full_name'],
                        $app['father_name'],
                        $app['cnic_or_bform'],
                        $app['dob'],
                        $app['gender'],
                        $app['contact_no'],
                        $app['email'],
                        $app['address'],
                        $app['program_id'],
                        $app['session_id'],
                        $app['session_id'],
                        $app['applied_semester_id'],
                        date('Y'),
                        date('Y-m-d')
                    ]);

                    if ($student_result && $user_result && $main_student_result) {
                        // Update application status to Admitted
                        $pdo->prepare("
                            UPDATE admission_applications 
                            SET application_status = 'Admitted' 
                            WHERE application_id = ?
                        ")->execute([$id]);

                        // --- 4. Auto-enroll in LMS courses matching program ---
                        try {
                            $enroll_stmt = $pdo->prepare("
                                INSERT IGNORE INTO lms_enrollments (student_user_id, course_id)
                                SELECT ?, c.course_id FROM courses c 
                                WHERE c.program_id = ? OR c.program_id IS NULL
                            ");
                            $enroll_stmt->execute([$new_user_id, $app['program_id']]);
                        } catch (PDOException $e) {
                            // LMS enrollment is best-effort, don't block admission
                        }
                        
                        // Commit transaction
                        $pdo->commit();
                        
                        setFlash('success', 'Application approved! Student created (ID: ' . $student_id . ', Login: ' . $login_id . ', Password: ' . $default_password . ')');
                        header('Location: index.php');
                        exit();
                    } else {
                        // Rollback if student creation fails
                        $pdo->rollBack();
                        setFlash('error', 'Failed to create student record');
                    }
                } else {
                    // Student already exists
                    $pdo->commit();
                    setFlash('success', 'Application reviewed successfully! Student already exists.');
                    header('Location: index.php');
                    exit();
                }
            } else {
                // For Rejected or Under Review - just commit
                $pdo->commit();
                setFlash('success', 'Application reviewed successfully! Status: ' . $status);
                header('Location: index.php');
                exit();
            }
        } else {
            $pdo->rollBack();
            setFlash('error', 'Failed to update application');
        }
        
    } catch (PDOException $e) {
        $pdo->rollBack();
        setFlash('error', 'Database Error: ' . $e->getMessage());
    }
}
?>
<div class="page-header">
    <div class="page-header-left">
        <h4><i class="fas fa-check"></i> Review Application</h4>
    </div>
    <div class="page-header-actions">
        <a href="index.php" class="btn btn-ghost">Back to List</a>
    </div>
</div>

<div class="grid-2">
    <div class="card">
        <div class="card-header">
            <h3>Application Summary</h3>
            <p>Details for verification prior to review</p>
        </div>
        <div class="card-content">
            <div class="detail-row">
                <div class="detail-label">Application No</div>
                <div class="detail-value"><strong><?= htmlspecialchars($app['temp_application_no']) ?></strong></div>
            </div>
            <div class="detail-row">
                <div class="detail-label">Student Name</div>
                <div class="detail-value"><?= htmlspecialchars($app['full_name']) ?></div>
            </div>
            <div class="detail-row">
                <div class="detail-label">Father Name</div>
                <div class="detail-value"><?= htmlspecialchars($app['father_name']) ?></div>
            </div>
            <div class="detail-row">
                <div class="detail-label">CNIC/B-Form</div>
                <div class="detail-value"><?= htmlspecialchars($app['cnic_or_bform'] ?? 'N/A') ?></div>
            </div>
            <div class="detail-row">
                <div class="detail-label">Department</div>
                <div class="detail-value"><?= htmlspecialchars($app['department_name'] ?? 'N/A') ?></div>
            </div>
            <div class="detail-row">
                <div class="detail-label">Submitted Date</div>
                <div class="detail-value"><?= date('d M Y', strtotime($app['submitted_at'])) ?></div>
            </div>
            <div class="detail-row">
                <div class="detail-label">Current Status</div>
                <div class="detail-value">
                    <span class="status-badge <?= htmlspecialchars($app['application_status']) ?>"><?= htmlspecialchars($app['application_status']) ?></span>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h3>Review Decision</h3>
            <p>Select approval decision for student application</p>
        </div>
        <div class="card-content">
            <form method="POST" id="reviewForm">
                <div class="field" style="margin-bottom:16px;">
                    <label>Decision *</label>
                    <select name="status" required id="decisionSelect">
                        <option value="Approved">Approve (Auto-create Student)</option>
                        <option value="Admitted">Admit (Auto-create Student)</option>
                        <option value="Rejected">Reject</option>
                        <option value="Under Review">Keep Under Review</option>
                    </select>
                    <small style="color:var(--text-secondary);margin-top:4px;display:block;">Selecting "Approve" or "Admit" will automatically create a student record.</small>
                </div>
                <div id="rejection_reason_div" style="display:none;margin-bottom:16px;">
                    <div class="field">
                        <label>Rejection Reason <small style="color:var(--danger);">(Required if Rejected)</small></label>
                        <textarea name="rejection_reason" rows="3" placeholder="Enter reason for rejection..."></textarea>
                    </div>
                </div>
                <div id="student_id_preview" style="display:none;margin-bottom:16px;">
                    <div class="alert alert-success">
                        <i class="fas fa-info-circle"></i>
                        Student will be created with ID: <strong id="previewStudentId">UNI-2024-00001</strong>
                    </div>
                </div>
                <div class="form-actions" style="margin-top:20px;padding-top:16px;border-top:1px solid var(--border);">
                    <a href="view.php?id=<?= $id ?>" class="btn btn-outline">Cancel</a>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-check"></i> Submit Review</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.getElementById('decisionSelect').addEventListener('change', function() {
    const rejectionDiv = document.getElementById('rejection_reason_div');
    const previewDiv = document.getElementById('student_id_preview');
    
    if (this.value === 'Rejected') {
        rejectionDiv.style.display = 'block';
        previewDiv.style.display = 'none';
    } else if (this.value === 'Approved' || this.value === 'Admitted') {
        rejectionDiv.style.display = 'none';
        previewDiv.style.display = 'block';
        
        // Get current year and generate preview ID
        const year = new Date().getFullYear();
        const randomNum = Math.floor(Math.random() * 90000) + 10000;
        document.getElementById('previewStudentId').textContent = 'UNI-' + year + '-' + randomNum;
    } else {
        rejectionDiv.style.display = 'none';
        previewDiv.style.display = 'none';
    }
});
</script>
<?php include __DIR__ . '/../includes/footer.php'; ?>
