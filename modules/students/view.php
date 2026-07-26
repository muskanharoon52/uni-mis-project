<?php
require_once '../../config/database.php';
$page_title = 'View Student';
include '../../includes/header.php';

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
?>
<div class="page-header d-flex justify-content-between align-items-center">
    <h5><i class="fas fa-user-graduate"></i> Student Details</h5>
    <div>
        <a href="index.php" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Back
        </a>
        <a href="edit.php?id=<?= $student['id'] ?>" class="btn btn-warning">
            <i class="fas fa-edit"></i> Edit
        </a>
    </div>
</div>

<div class="row">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h6 class="mb-0"><i class="fas fa-user"></i> Personal Information</h6>
            </div>
            <div class="card-body">
                <table class="table table-bordered">
                    <tr>
                        <th style="width:40%;">Student ID</th>
                        <td><strong><?= $student['student_id'] ?? 'N/A' ?></strong></td>
                    </tr>
                    <tr>
                        <th>Full Name</th>
                        <td><?= $student['student_name'] ?? 'N/A' ?></td>
                    </tr>
                    <tr>
                        <th>Father Name</th>
                        <td><?= $student['father_name'] ?? 'N/A' ?></td>
                    </tr>
                    <tr>
                        <th>CNIC/B-Form</th>
                        <td><?= $student['cnic_or_bform'] ?? 'N/A' ?></td>
                    </tr>
                    <tr>
                        <th>Date of Birth</th>
                        <td><?= isset($student['dob']) ? date('d M Y', strtotime($student['dob'])) : 'N/A' ?></td>
                    </tr>
                    <tr>
                        <th>Gender</th>
                        <td><?= $student['gender'] ?? 'N/A' ?></td>
                    </tr>
                    <tr>
                        <th>Email</th>
                        <td><?= $student['email'] ?? 'N/A' ?></td>
                    </tr>
                    <tr>
                        <th>Contact Number</th>
                        <td><?= $student['contact_no'] ?? 'N/A' ?></td>
                    </tr>
                    <tr>
                        <th>Address</th>
                        <td><?= $student['address'] ?? 'N/A' ?></td>
                    </tr>
                </table>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card">
            <div class="card-header bg-success text-white">
                <h6 class="mb-0"><i class="fas fa-graduation-cap"></i> Academic Information</h6>
            </div>
            <div class="card-body">
                <table class="table table-bordered">
                    <tr>
                        <th style="width:40%;">Department</th>
                        <td><?= $student['department_name'] ?? 'N/A' ?></td>
                    </tr>
                    <tr>
                        <th>Status</th>
                        <td>
                            <?php 
                            $status = $student['status'] ?? 'active';
                            $badge_color = match($status) {
                                'active' => 'success',
                                'inactive' => 'secondary',
                                'graduated' => 'primary',
                                'suspended' => 'danger',
                                default => 'secondary'
                            };
                            ?>
                            <span class="badge bg-<?= $badge_color ?>"><?= ucfirst($status) ?></span>
                        </td>
                    </tr>
                    <?php if (!empty($student['enrollment_date'])): ?>
                    <tr>
                        <th>Enrollment Date</th>
                        <td><?= date('d M Y', strtotime($student['enrollment_date'])) ?></td>
                    </tr>
                    <?php endif; ?>
                    <?php if (!empty($student['created_at'])): ?>
                    <tr>
                        <th>Added Date</th>
                        <td><?= date('d M Y, h:i A', strtotime($student['created_at'])) ?></td>
                    </tr>
                    <?php endif; ?>
                    <?php if (!empty($student['application_id'])): ?>
                    <tr>
                        <th>Application ID</th>
                        <td><?= $student['application_id'] ?></td>
                    </tr>
                    <?php endif; ?>
                </table>
            </div>
        </div>
    </div>
</div>
<?php include '../../includes/footer.php'; ?>