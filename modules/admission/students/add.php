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
        
        // Add enrollment_date if column exists
        $columns = $pdo->query("SHOW COLUMNS FROM admission_students")->fetchAll(PDO::FETCH_COLUMN);
        if (in_array('enrollment_date', $columns)) {
            $data['enrollment_date'] = date('Y-m-d');
        }
        
        $sql = "INSERT INTO admission_students SET ";
        $set_parts = [];
        foreach ($data as $key => $value) {
            $set_parts[] = "$key = :$key";
        }
        $sql .= implode(", ", $set_parts);
        
        $stmt = $pdo->prepare($sql);
        if ($stmt->execute($data)) {
            // Update application status if linked
            if (!empty($_POST['application_id'])) {
                $pdo->prepare("UPDATE admission_applications SET application_status = 'Admitted' WHERE application_id = ?")
                   ->execute([$_POST['application_id']]);
            }
            setFlash('success', 'Student added successfully! Student ID: ' . $student_id);
            header('Location: index.php');
            exit();
        }
    } catch (PDOException $e) {
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