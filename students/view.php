<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
requireSSO();

global $conn;

$id = isset($_GET['id']) ? $_GET['id'] : '';

if (empty($id)) {
    header('Location: index.php');
    exit();
}

// Get student details with user info
$sql = "SELECT s.*, u.user_id, u.full_name, u.email, u.phone, p.program_name 
        FROM students s 
        LEFT JOIN users u ON s.user_id = u.user_id 
        LEFT JOIN programs p ON s.program_id = p.program_id 
        WHERE s.student_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $id);
$stmt->execute();
$result = $stmt->get_result();
$student = $result->fetch_assoc();

if (!$student) {
    header('Location: index.php');
    exit();
}

$success = isset($_GET['success']) ? $_GET['success'] : '';

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>

<div class="main-content">
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2><i class="fas fa-user-graduate"></i> Student Profile</h2>
            <div>
                <a href="edit.php?id=<?php echo urlencode($id); ?>" class="btn btn-primary">
                    <i class="fas fa-edit"></i> Edit
                </a>
                <a href="index.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Back
                </a>
            </div>
        </div>

        <?php if ($success == 'updated'): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <i class="fas fa-check-circle"></i> Student updated successfully!
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="card">
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <h5 class="mb-3"><i class="fas fa-user text-primary"></i> Personal Information</h5>
                        <table class="table table-bordered">
                            <tr>
                                <th style="width:150px;">Student ID</th>
                                <td><strong><?php echo htmlspecialchars($student['student_id']); ?></strong></td>
                            </tr>
                            <tr>
                                <th>Roll No</th>
                                <td><?php echo htmlspecialchars($student['roll_no'] ?? 'N/A'); ?></td>
                            </tr>
                            <tr>
                                <th>Full Name</th>
                                <td><strong><?php echo htmlspecialchars($student['full_name']); ?></strong></td>
                            </tr>
                            <tr>
                                <th>Father's Name</th>
                                <td><?php echo htmlspecialchars($student['father_name'] ?? 'N/A'); ?></td>
                            </tr>
                            <tr>
                                <th>Email</th>
                                <td><?php echo htmlspecialchars($student['email']); ?></td>
                            </tr>
                            <tr>
                                <th>Phone</th>
                                <td><?php echo htmlspecialchars($student['phone'] ?? 'N/A'); ?></td>
                            </tr>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <h5 class="mb-3"><i class="fas fa-graduation-cap text-success"></i> Academic Information</h5>
                        <table class="table table-bordered">
                            <tr>
                                <th style="width:150px;">Program</th>
                                <td><?php echo htmlspecialchars($student['program_name'] ?? 'N/A'); ?></td>
                            </tr>
                            <tr>
                                <th>Semester</th>
                                <td>
                                    <?php 
                                    $semester_num = $student['semester'] ?? 1;
                                    $ordinal = ['1st', '2nd', '3rd', '4th', '5th', '6th', '7th', '8th'];
                                    echo ($semester_num >= 1 && $semester_num <= 8) 
                                        ? $ordinal[$semester_num - 1] . ' Semester' 
                                        : 'Semester ' . $semester_num;
                                    ?>
                                </td>
                            </tr>
                            <tr>
                                <th>Section</th>
                                <td><?php echo htmlspecialchars($student['section'] ?? 'N/A'); ?></td>
                            </tr>
                            <tr>
                                <th>Batch Year</th>
                                <td><?php echo htmlspecialchars($student['batch_year'] ?? 'N/A'); ?></td>
                            </tr>
                            <tr>
                                <th>Session</th>
                                <td><?php echo htmlspecialchars($student['session'] ?? 'N/A'); ?></td>
                            </tr>
                            <tr>
                                <th>Status</th>
                                <td>
                                    <span class="badge bg-<?php echo $student['status'] == 'active' ? 'success' : ($student['status'] == 'confirmed' ? 'info' : 'secondary'); ?>">
                                        <?php echo ucfirst($student['status'] ?? 'N/A'); ?>
                                    </span>
                                </td>
                            </tr>
                            <tr>
                                <th>Enrollment Date</th>
                                <td><?php echo date('M d, Y', strtotime($student['enrollment_date'] ?? 'now')); ?></td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>