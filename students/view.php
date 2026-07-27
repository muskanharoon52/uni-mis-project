<?php
require_once __DIR__ . '/../config/db_connect.php';
require_once __DIR__ . '/../modules/sso/includes/auth.php';
requireSSO();

global $conn;

$id = isset($_GET['id']) ? $_GET['id'] : '';

if (empty($id)) {
    header('Location: index.php?error=Invalid student ID');
    exit();
}

// Use the correct table name
$table_name = 'admission_students';

// Get student details
$sql = "SELECT s.*, p.program_name, p.program_code 
        FROM $table_name s 
        LEFT JOIN programs p ON s.program_id = p.program_id 
        WHERE s.student_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $id);
$stmt->execute();
$result = $stmt->get_result();
$student = $result->fetch_assoc();

if (!$student) {
    header('Location: index.php?error=Student not found');
    exit();
}

$success = isset($_GET['success']) ? $_GET['success'] : '';

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>

<style>
    .main-content { margin-left: 250px; padding: 20px; }
    .profile-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 20px;
        border-radius: 10px;
        margin-bottom: 20px;
    }
    .profile-header h2 { color: white; }
    .info-table th { width: 150px; background: #f8f9fa; }
    .status-badge { padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: 500; }
    .status-active { background: #d4edda; color: #155724; }
    .status-confirmed { background: #cce5ff; color: #004085; }
    .status-pending { background: #fff3cd; color: #856404; }
    .status-inactive { background: #e2e3e5; color: #383d41; }
    .status-graduated { background: #d4edda; color: #155724; }
    @media (max-width: 768px) { .main-content { margin-left: 0; } }
</style>

<div class="main-content">
    <div class="container-fluid">
        <div class="profile-header">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2><i class="fas fa-user-graduate"></i> Student Profile</h2>
                    <p class="mb-0"><?php echo htmlspecialchars($student['full_name'] ?? $student['student_name'] ?? 'N/A'); ?></p>
                </div>
                <div>
                    <a href="edit.php?id=<?php echo urlencode($id); ?>" class="btn btn-light">
                        <i class="fas fa-edit"></i> Edit
                    </a>
                    <a href="index.php" class="btn btn-outline-light">
                        <i class="fas fa-arrow-left"></i> Back
                    </a>
                </div>
            </div>
        </div>

        <?php if ($success == 'updated'): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <i class="fas fa-check-circle"></i> Student updated successfully!
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="row">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5><i class="fas fa-user text-primary"></i> Personal Information</h5>
                    </div>
                    <div class="card-body">
                        <table class="table table-bordered info-table">
                            <tr>
                                <th>Student ID</th>
                                <td><strong><?php echo htmlspecialchars($student['student_id']); ?></strong></td>
                            </tr>
                            <tr>
                                <th>Full Name</th>
                                <td><strong><?php echo htmlspecialchars($student['full_name'] ?? $student['student_name'] ?? 'N/A'); ?></strong></td>
                            </tr>
                            <?php if (!empty($student['student_name']) && $student['student_name'] != $student['full_name']): ?>
                            <tr>
                                <th>Student Name</th>
                                <td><?php echo htmlspecialchars($student['student_name']); ?></td>
                            </tr>
                            <?php endif; ?>
                            <tr>
                                <th>Father's Name</th>
                                <td><?php echo htmlspecialchars($student['father_name'] ?? 'N/A'); ?></td>
                            </tr>
                            <tr>
                                <th>Email</th>
                                <td><?php echo htmlspecialchars($student['email'] ?? 'N/A'); ?></td>
                            </tr>
                            <tr>
                                <th>Phone</th>
                                <td><?php echo htmlspecialchars($student['contact_no'] ?? 'N/A'); ?></td>
                            </tr>
                            <?php if (!empty($student['cnic_or_bform'])): ?>
                            <tr>
                                <th>CNIC/B-Form</th>
                                <td><?php echo htmlspecialchars($student['cnic_or_bform']); ?></td>
                            </tr>
                            <?php endif; ?>
                            <?php if (!empty($student['dob'])): ?>
                            <tr>
                                <th>Date of Birth</th>
                                <td><?php echo date('d M Y', strtotime($student['dob'])); ?></td>
                            </tr>
                            <?php endif; ?>
                            <?php if (!empty($student['gender'])): ?>
                            <tr>
                                <th>Gender</th>
                                <td><?php echo htmlspecialchars($student['gender']); ?></td>
                            </tr>
                            <?php endif; ?>
                            <?php if (!empty($student['address'])): ?>
                            <tr>
                                <th>Address</th>
                                <td><?php echo htmlspecialchars($student['address']); ?></td>
                            </tr>
                            <?php endif; ?>
                        </table>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5><i class="fas fa-graduation-cap text-success"></i> Academic Information</h5>
                    </div>
                    <div class="card-body">
                        <table class="table table-bordered info-table">
                            <tr>
                                <th>Program</th>
                                <td><?php echo htmlspecialchars($student['program_name'] ?? 'N/A'); ?></td>
                            </tr>
                            <tr>
                                <th>Program Code</th>
                                <td><?php echo htmlspecialchars($student['program_code'] ?? 'N/A'); ?></td>
                            </tr>
                            <tr>
                                <th>Status</th>
                                <td>
                                    <?php 
                                    $status = $student['status'] ?? 'pending';
                                    $status_class = match($status) {
                                        'active' => 'active',
                                        'confirmed' => 'confirmed',
                                        'pending' => 'pending',
                                        'inactive' => 'inactive',
                                        'graduated' => 'graduated',
                                        default => 'pending'
                                    };
                                    ?>
                                    <span class="status-badge status-<?php echo $status_class; ?>">
                                        <?php echo ucfirst($status); ?>
                                    </span>
                                </td>
                            </tr>
                            <?php if (!empty($student['application_id'])): ?>
                            <tr>
                                <th>Application ID</th>
                                <td><?php echo htmlspecialchars($student['application_id']); ?></td>
                            </tr>
                            <?php endif; ?>
                            <tr>
                                <th>Created At</th>
                                <td><?php echo !empty($student['created_at']) ? date('d M Y h:i A', strtotime($student['created_at'])) : 'N/A'; ?></td>
                            </tr>
                            <tr>
                                <th>Last Updated</th>
                                <td><?php echo !empty($student['updated_at']) ? date('d M Y h:i A', strtotime($student['updated_at'])) : 'N/A'; ?></td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>