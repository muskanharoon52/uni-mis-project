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
<div class="page-header"><h5><i class="fas fa-plus"></i> Add Student</h5></div>

<?php
$flash = getFlash();
if ($flash): ?>
    <div class="alert alert-<?= $flash['type'] ?> alert-dismissible fade show">
        <?= $flash['message'] ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<form method="POST" class="row g-3">
    <div class="col-12"><h6 class="text-primary">Personal Information</h6></div>
    
    <div class="col-md-6">
        <label class="form-label">Student Name *</label>
        <input type="text" name="student_name" class="form-control" required>
    </div>
    
    <div class="col-md-6">
        <label class="form-label">Father Name *</label>
        <input type="text" name="father_name" class="form-control" required>
    </div>
    
    <div class="col-md-4">
        <label class="form-label">CNIC/B-Form *</label>
        <input type="text" name="cnic_or_bform" class="form-control" placeholder="XXXXX-XXXXXXX-X" required>
    </div>
    
    <div class="col-md-4">
        <label class="form-label">Date of Birth *</label>
        <input type="date" name="dob" class="form-control" required>
    </div>
    
    <div class="col-md-4">
        <label class="form-label">Gender *</label>
        <select name="gender" class="form-select" required>
            <option value="">Select</option>
            <option value="Male">Male</option>
            <option value="Female">Female</option>
            <option value="Other">Other</option>
        </select>
    </div>
    
    <div class="col-md-6">
        <label class="form-label">Email *</label>
        <input type="email" name="email" class="form-control" required>
    </div>
    
    <div class="col-md-6">
        <label class="form-label">Contact Number *</label>
        <input type="text" name="contact_no" class="form-control" placeholder="03XX-XXXXXXX" required>
    </div>
    
    <div class="col-md-12">
        <label class="form-label">Address</label>
        <textarea name="address" class="form-control" rows="2"></textarea>
    </div>
    
    <div class="col-12"><h6 class="text-primary mt-2">Academic Information</h6></div>
    
    <div class="col-md-6">
        <label class="form-label">Department/Program *</label>
        <select name="department_id" class="form-select" required>
            <option value="">-- Select Department --</option>
            <?php foreach($departments as $d): ?>
            <option value="<?= $d['department_id'] ?>">
                <?= $d['department_name'] ?> (<?= $d['department_code'] ?? 'N/A' ?>)
            </option>
            <?php endforeach; ?>
        </select>
    </div>
    
    <div class="col-md-6">
        <label class="form-label">From Application (Optional)</label>
        <select name="application_id" class="form-select">
            <option value="">Direct Admission</option>
            <?php foreach($applications as $app): ?>
            <option value="<?= $app['application_id'] ?>">
                <?= $app['full_name'] ?> (<?= $app['temp_application_no'] ?>)
            </option>
            <?php endforeach; ?>
        </select>
        <?php if (empty($applications)): ?>
        <small class="text-muted">No approved applications available</small>
        <?php endif; ?>
    </div>
    
    <div class="col-12">
        <button type="submit" class="btn btn-primary">
            <i class="fas fa-user-plus"></i> Add Student
        </button>
        <a href="index.php" class="btn btn-secondary">Cancel</a>
    </div>
</form>
<?php include __DIR__ . '/../includes/footer.php'; ?>