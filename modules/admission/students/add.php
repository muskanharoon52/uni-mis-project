<?php
require_once __DIR__ . '/../config/database.php';
$page_title = 'Add Student';
include __DIR__ . '/../includes/header.php';

// Get departments
$departments = $pdo->query("SELECT * FROM departments WHERE status='active' ORDER BY department_name")->fetchAll();

// Get approved applications to link (using correct table name)
$applications = $pdo->query("
    SELECT application_id, temp_application_no, full_name 
    FROM admission_applications 
    WHERE application_status = 'Approved' 
    ORDER BY application_id DESC
")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $student_id = generateStudentId();
        
        $data = [
            'student_id' => $student_id,
            'application_id' => !empty($_POST['application_id']) ? $_POST['application_id'] : null,
            'student_name' => sanitize($_POST['student_name']),
            'father_name' => sanitize($_POST['father_name']),
            'cnic_or_bform' => sanitize($_POST['cnic_or_bform']),
            'dob' => $_POST['dob'],
            'gender' => $_POST['gender'],
            'contact_no' => sanitize($_POST['contact_no']),
            'email' => sanitize($_POST['email']),
            'address' => sanitize($_POST['address']),
            'program_id' => $_POST['department_id'],
            'status' => 'active'
        ];
        
        $pdo->beginTransaction();

        // 1. Create in `users` table for SSO authentication
        $default_password = 'student123';
        $password_hash = password_hash($default_password, PASSWORD_DEFAULT);
        $username = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', explode(' ', $data['student_name'])[0])) . rand(100, 999);
        $login_id = strval(9000 + (int)date('Y') % 1000 + rand(100, 999));
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
        $user_stmt->execute([$data['student_name'], $username, $login_id, $data['email'], $data['contact_no'], $password_hash, $data['program_id']]);

        // 2. Create in `admission_students`
        $sql = "INSERT INTO admission_students SET ";
        $set_parts = [];
        foreach ($data as $key => $value) {
            $set_parts[] = "$key = :$key";
        }
        $sql .= implode(", ", $set_parts);
        $stmt = $pdo->prepare($sql);
        $stmt->execute($data);

        // 3. Create in `students` (main table)
        $roll_no = strtoupper(substr(explode(' ', $data['student_name'])[0], 0, 3)) . '-' . date('Y') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
        $main_student_stmt = $pdo->prepare("
            INSERT INTO students 
            (application_id, roll_no, full_name, father_name, cnic_or_bform, 
             dob, gender, contact_no, email, address, program_id, 
             admission_session_id, current_session_id, current_semester_id, 
             batch_year, admission_date, status)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1, 1, 1, ?, CURDATE(), 'Active')
        ");
        $main_student_stmt->execute([
            $data['application_id'], $roll_no, $data['student_name'], $data['father_name'],
            $data['cnic_or_bform'], $data['dob'], $data['gender'], $data['contact_no'],
            $data['email'], $data['address'], $data['program_id'], date('Y')
        ]);

        // Update application status if linked
        if (!empty($_POST['application_id'])) {
            $pdo->prepare("UPDATE admission_applications SET application_status = 'Admitted' WHERE application_id = ?")
               ->execute([$_POST['application_id']]);
        }

        $pdo->commit();
        setFlash('success', 'Student added! ID: ' . $student_id . ' | Login: ' . $login_id . ' | Password: ' . $default_password);
        header('Location: index.php');
        exit();
    } catch (PDOException $e) {
        $pdo->rollBack();
        setFlash('error', 'Database Error: ' . $e->getMessage());
    }
}
?>
<div class="page-header">
    <div class="page-header-left">
        <h4><i class="fas fa-user-plus"></i> Add New Student</h4>
    </div>
    <div class="page-header-actions">
        <a href="index.php" class="btn btn-outline"><i class="fas fa-arrow-left"></i> Back</a>
    </div>
</div>

<?php
$flash = getFlash();
if ($flash): ?>
    <div class="alert alert-<?= $flash['type'] === 'error' ? 'error' : 'success' ?>">
        <?= $flash['message'] ?>
    </div>
<?php endif; ?>

<div class="form-container">
    <form method="POST">
        <h6 style="font-size:.92rem;font-weight:700;color:var(--navy);margin-bottom:16px;padding-bottom:8px;border-bottom:1px solid var(--border);">Personal Information</h6>
        
        <div class="form-row">
            <div class="form-group">
                <label>Student Name *</label>
                <input type="text" name="student_name" required placeholder="Full student name">
            </div>
            <div class="form-group">
                <label>Father Name *</label>
                <input type="text" name="father_name" required placeholder="Father's name">
            </div>
        </div>
        
        <div class="form-row">
            <div class="form-group">
                <label>CNIC/B-Form *</label>
                <input type="text" name="cnic_or_bform" placeholder="XXXXX-XXXXXXX-X" required>
            </div>
            <div class="form-group">
                <label>Date of Birth *</label>
                <input type="date" name="dob" required>
            </div>
        </div>
        
        <div class="form-row">
            <div class="form-group">
                <label>Gender *</label>
                <select name="gender" required>
                    <option value="">Select Gender</option>
                    <option value="Male">Male</option>
                    <option value="Female">Female</option>
                    <option value="Other">Other</option>
                </select>
            </div>
            <div class="form-group">
                <label>Email *</label>
                <input type="email" name="email" required placeholder="student@example.com">
            </div>
        </div>
        
        <div class="form-row">
            <div class="form-group">
                <label>Contact Number *</label>
                <input type="text" name="contact_no" placeholder="03XX-XXXXXXX" required>
            </div>
            <div class="form-group">
                <label>Address</label>
                <textarea name="address" rows="2" placeholder="Residential address"></textarea>
            </div>
        </div>
        
        <h6 style="font-size:.92rem;font-weight:700;color:var(--navy);margin-top:20px;margin-bottom:16px;padding-bottom:8px;border-bottom:1px solid var(--border);">Academic Assignment</h6>
        
        <div class="form-row">
            <div class="form-group">
                <label>Department/Program *</label>
                <select name="department_id" required>
                    <option value="">-- Select Department --</option>
                    <?php foreach($departments as $d): ?>
                    <option value="<?= $d['department_id'] ?>">
                        <?= htmlspecialchars($d['department_name']) ?> (<?= htmlspecialchars($d['department_code'] ?? 'N/A') ?>)
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>From Application (Optional)</label>
                <select name="application_id">
                    <option value="">Direct Admission</option>
                    <?php foreach($applications as $app): ?>
                    <option value="<?= $app['application_id'] ?>">
                        <?= htmlspecialchars($app['full_name']) ?> (<?= htmlspecialchars($app['temp_application_no']) ?>)
                    </option>
                    <?php endforeach; ?>
                </select>
                <?php if (empty($applications)): ?>
                <small class="muted">No approved pending applications available</small>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="form-actions">
            <a href="index.php" class="btn btn-outline">Cancel</a>
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-user-plus"></i> Add Student
            </button>
        </div>
    </form>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
