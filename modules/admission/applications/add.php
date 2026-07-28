<?php
require_once __DIR__ . '/../config/database.php';
$page_title = 'Add Application';
include __DIR__ . '/../includes/header.php';

// Get departments for dropdown
$departments = $pdo->query("SELECT * FROM departments WHERE status='active' ORDER BY department_name")->fetchAll();

// Get sessions
$sessions = $pdo->query("SELECT * FROM sessions ORDER BY session_id DESC")->fetchAll();

// Get semesters
$semesters = $pdo->query("SELECT * FROM semesters ORDER BY semester_id")->fetchAll();

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
<div class="page-header">
    <div class="page-header-left">
        <h4><i class="fas fa-plus"></i> New Application</h4>
    </div>
    <div class="page-header-actions">
        <a href="index.php" class="btn btn-ghost">Back to List</a>
    </div>
</div>

<?php
$flash = getFlash();
if ($flash): ?>
    <div class="alert alert-<?= $flash['type'] ?>">
        <?= $flash['message'] ?>
    </div>
<?php endif; ?>

<div class="form-container">
    <form method="POST">
        <h6 style="font-size:.92rem;font-weight:700;color:var(--navy);margin-bottom:16px;padding-bottom:8px;border-bottom:1px solid var(--border);">Personal Information</h6>

        <div class="form-row">
            <div class="form-group">
                <label>Full Name *</label>
                <input type="text" name="full_name" required placeholder="Enter full student name">
            </div>
            <div class="form-group">
                <label>Father Name *</label>
                <input type="text" name="father_name" required placeholder="Enter father's name">
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
                <input type="text" name="address" placeholder="Residential address">
            </div>
        </div>

        <h6 style="font-size:.92rem;font-weight:700;color:var(--navy);margin-top:20px;margin-bottom:16px;padding-bottom:8px;border-bottom:1px solid var(--border);">Academic Program Selection</h6>

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
                <label>Session *</label>
                <select name="session_id" required>
                    <option value="">-- Select Session --</option>
                    <?php foreach($sessions as $s): ?>
                    <option value="<?= $s['session_id'] ?>">
                        <?= htmlspecialchars($s['session_name'] ?? $s['session_id']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label>Semester *</label>
                <select name="semester_id" required>
                    <option value="">-- Select Semester --</option>
                    <?php foreach($semesters as $sem): ?>
                    <option value="<?= $sem['semester_id'] ?>">
                        <?= htmlspecialchars($sem['semester_name'] ?? $sem['semester_id']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group"></div>
        </div>

        <div class="form-actions">
            <a href="index.php" class="btn btn-outline">Cancel</a>
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-paper-plane"></i> Submit Application
            </button>
        </div>
    </form>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
