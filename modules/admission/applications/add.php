<?php
require_once __DIR__ . '/../config/database.php';
$page_title = 'Add Application';
include __DIR__ . '/../includes/header.php';

// Get departments for dropdown
$departments = $pdo->query("SELECT * FROM departments WHERE status='active' ORDER BY department_name")->fetchAll();

// Get sessions
$sessions = $pdo->query("SELECT * FROM sessions ORDER BY session_id DESC")->fetchAll();

// Get semesters - FIXED: Using DISTINCT to remove duplicates
$semesters = $pdo->query("SELECT DISTINCT semester_id, semester_name FROM semesters ORDER BY CAST(SUBSTRING(semester_name, 10) AS UNSIGNED)")->fetchAll();

// Alternative if semester_name is like "Semester 1", "Semester 2", etc.
// $semesters = $pdo->query("SELECT DISTINCT semester_id, semester_name FROM semesters ORDER BY semester_id")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $application_no = generateApplicationNo();
        
        $data = [
            'temp_application_no' => $application_no,
            'full_name' => sanitize($_POST['full_name']),
            'father_name' => sanitize($_POST['father_name']),
            'cnic_or_bform' => sanitize($_POST['cnic_or_bform']),
            'dob' => $_POST['dob'],
            'gender' => $_POST['gender'],
            'contact_no' => sanitize($_POST['contact_no']),
            'email' => sanitize($_POST['email']),
            'address' => sanitize($_POST['address']),
            'program_id' => $_POST['department_id'],
            'session_id' => $_POST['session_id'],
            'applied_semester_id' => $_POST['semester_id'],
            'application_status' => 'Submitted',
            'submitted_at' => date('Y-m-d H:i:s')
        ];
        
        $sql = "INSERT INTO admission_applications SET 
                temp_application_no = :temp_application_no,
                full_name = :full_name,
                father_name = :father_name,
                cnic_or_bform = :cnic_or_bform,
                dob = :dob,
                gender = :gender,
                contact_no = :contact_no,
                email = :email,
                address = :address,
                program_id = :program_id,
                session_id = :session_id,
                applied_semester_id = :applied_semester_id,
                application_status = :application_status,
                submitted_at = :submitted_at";
        
        $stmt = $pdo->prepare($sql);
        if ($stmt->execute($data)) {
            setFlash('success', 'Application submitted successfully! Application #: ' . $application_no);
            header('Location: index.php');
            exit();
        }
    } catch (PDOException $e) {
        setFlash('error', 'Database Error: ' . $e->getMessage());
    }
}
?>
<div class="page-header"><h5><i class="fas fa-plus"></i> New Application</h5></div>

<?php
$flash = getFlash();
if ($flash): ?>
    <div class="alert alert-<?= $flash['type'] ?> alert-dismissible fade show">
        <?= $flash['message'] ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<form method="POST" class="row g-3">
    <!-- Personal Information -->
    <div class="col-12"><h6 class="text-primary">Personal Information</h6></div>
    
    <div class="col-md-6">
        <label class="form-label">Full Name *</label>
        <input type="text" name="full_name" class="form-control" required>
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
            <option value="">Select Gender</option>
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
    
    <!-- Academic Information -->
    <div class="col-12"><h6 class="text-primary mt-2">Academic Information</h6></div>
    
    <div class="col-md-4">
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
    
    <div class="col-md-4">
        <label class="form-label">Session *</label>
        <select name="session_id" class="form-select" required>
            <option value="">-- Select Session --</option>
            <?php foreach($sessions as $s): ?>
            <option value="<?= $s['session_id'] ?>">
                <?= $s['session_name'] ?? $s['session_id'] ?>
            </option>
            <?php endforeach; ?>
        </select>
    </div>
    
    <div class="col-md-4">
        <label class="form-label">Semester *</label>
        <select name="semester_id" class="form-select" required>
            <option value="">-- Select Semester --</option>
            <?php 
            // FIXED: Using array to track seen semester names
            $seen_semesters = [];
            foreach($semesters as $sem): 
                // Skip if we've already seen this semester name
                if (in_array($sem['semester_name'], $seen_semesters)) continue;
                $seen_semesters[] = $sem['semester_name'];
            ?>
            <option value="<?= $sem['semester_id'] ?>">
                <?= $sem['semester_name'] ?? $sem['semester_id'] ?>
            </option>
            <?php endforeach; ?>
        </select>
    </div>
    
    <div class="col-12">
        <button type="submit" class="btn btn-primary">
            <i class="fas fa-paper-plane"></i> Submit Application
        </button>
        <a href="index.php" class="btn btn-secondary">Cancel</a>
    </div>
</form>
<?php include __DIR__ . '/../includes/footer.php'; ?>