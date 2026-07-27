<?php
require_once __DIR__ . '/../config/database.php';
$page_title = 'View Student';
include __DIR__ . '/../includes/header.php';

$id = $_GET['id'] ?? 0;

$stmt = $pdo->prepare("
    SELECT s.*, d.department_name 
    FROM admission_students s 
    LEFT JOIN departments d ON s.program_id = d.department_id 
    WHERE s.id = ?
");
$stmt->execute([$id]);
$student = $stmt->fetch();

if (!$student) {
    setFlash('error', 'Student not found');
    header('Location: index.php');
    exit();
}

$status = $student['status'] ?? 'active';
$badge_styles = match($status) {
    'active' => 'background:var(--success-bg);color:var(--success);border:1px solid var(--success-border);',
    'inactive' => 'background:var(--secondary-bg);color:var(--secondary);border:1px solid var(--secondary-border);',
    'graduated' => 'background:var(--primary-bg);color:var(--primary);border:1px solid var(--primary-border);',
    'suspended' => 'background:var(--danger-bg);color:var(--danger);border:1px solid var(--danger-border);',
    default => 'background:var(--secondary-bg);color:var(--secondary);border:1px solid var(--secondary-border);'
};
?>
<div class="page-header">
    <div class="page-header-left">
        <h4><i class="fas fa-user-graduate"></i> Student Details</h4>
    </div>
    <div class="page-header-actions">
        <a href="index.php" class="btn btn-outline">
            <i class="fas fa-arrow-left"></i> Back
        </a>
        <a href="edit.php?id=<?= $student['id'] ?>" class="btn btn-primary">
            <i class="fas fa-edit"></i> Edit
        </a>
    </div>
</div>

<div class="grid-2">
    <div class="card">
        <div class="card-header">
            <div>
                <h3>Personal Information</h3>
                <p>Student contact and demographic details</p>
            </div>
        </div>
        <div class="card-content">
            <div class="detail-row">
                <div class="detail-label">Student ID</div>
                <div class="detail-value"><strong><?= htmlspecialchars($student['student_id'] ?? 'N/A') ?></strong></div>
            </div>
            <div class="detail-row">
                <div class="detail-label">Full Name</div>
                <div class="detail-value"><?= htmlspecialchars($student['student_name'] ?? 'N/A') ?></div>
            </div>
            <div class="detail-row">
                <div class="detail-label">Father Name</div>
                <div class="detail-value"><?= htmlspecialchars($student['father_name'] ?? 'N/A') ?></div>
            </div>
            <div class="detail-row">
                <div class="detail-label">CNIC/B-Form</div>
                <div class="detail-value"><?= htmlspecialchars($student['cnic_or_bform'] ?? 'N/A') ?></div>
            </div>
            <div class="detail-row">
                <div class="detail-label">Date of Birth</div>
                <div class="detail-value"><?= isset($student['dob']) ? date('d M Y', strtotime($student['dob'])) : 'N/A' ?></div>
            </div>
            <div class="detail-row">
                <div class="detail-label">Gender</div>
                <div class="detail-value"><?= htmlspecialchars($student['gender'] ?? 'N/A') ?></div>
            </div>
            <div class="detail-row">
                <div class="detail-label">Email</div>
                <div class="detail-value"><?= htmlspecialchars($student['email'] ?? 'N/A') ?></div>
            </div>
            <div class="detail-row">
                <div class="detail-label">Contact Number</div>
                <div class="detail-value"><?= htmlspecialchars($student['contact_no'] ?? 'N/A') ?></div>
            </div>
            <div class="detail-row">
                <div class="detail-label">Address</div>
                <div class="detail-value"><?= htmlspecialchars($student['address'] ?? 'N/A') ?></div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <div>
                <h3>Academic & Enrollment Status</h3>
                <p>Department assignment and record metadata</p>
            </div>
        </div>
        <div class="card-content">
            <div class="detail-row">
                <div class="detail-label">Department</div>
                <div class="detail-value"><?= htmlspecialchars($student['department_name'] ?? 'N/A') ?></div>
            </div>
            <div class="detail-row">
                <div class="detail-label">Status</div>
                <div class="detail-value">
                    <?php $status = strtolower($student['status'] ?? 'active'); ?>
                    <span class="status-badge <?= $status ?>"><?= ucfirst($status) ?></span>
                </div>
            </div>
            <?php if (!empty($student['enrollment_date'])): ?>
            <div class="detail-row">
                <div class="detail-label">Enrollment Date</div>
                <div class="detail-value"><?= date('d M Y', strtotime($student['enrollment_date'])) ?></div>
            </div>
            <?php endif; ?>
            <?php if (!empty($student['created_at'])): ?>
            <div class="detail-row">
                <div class="detail-label">Added Date</div>
                <div class="detail-value"><?= date('d M Y, h:i A', strtotime($student['created_at'])) ?></div>
            </div>
            <?php endif; ?>
            <?php if (!empty($student['application_id'])): ?>
            <div class="detail-row">
                <div class="detail-label">Application Reference</div>
                <div class="detail-value"><?= htmlspecialchars($student['application_id']) ?></div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
