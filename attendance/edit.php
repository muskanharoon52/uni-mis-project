<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
requireLogin();

$conn = getConnection();
$error = '';
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) {
    header("Location: index.php?error=Invalid ID");
    exit;
}

// Fetch existing record
$query = "SELECT * FROM attendance WHERE attendance_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$attendance = $result->fetch_assoc();
$stmt->close();

if (!$attendance) {
    header("Location: index.php?error=Record not found");
    exit;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $status = $_POST['status'] ?? '';
    $remark = trim($_POST['remark'] ?? '');

    if (empty($status)) {
        $error = "Please select a status";
    } else {
        // Remove faculty_id from update query
        $update_query = "UPDATE attendance SET status = ?, remark = ? 
                         WHERE attendance_id = ?";
        $update_stmt = $conn->prepare($update_query);
        $update_stmt->bind_param("ssi", $status, $remark, $id);
        
        if ($update_stmt->execute()) {
            header("Location: index.php?success=Attendance updated successfully!");
            exit;
        } else {
            $error = "Error updating attendance: " . $conn->error;
        }
        $update_stmt->close();
    }
}

// ============================================
// HEADER INCLUDE
// ============================================
require_once __DIR__ . '/../includes/header.php';
$page_title = 'Edit Attendance';
include __DIR__ . '/../includes/sidebar.php';
?>

<style>
    .edit-content {
        margin-left: 250px;
        padding: 20px;
        min-height: 100vh;
        background: #f5f6fa;
    }
    
    .form-container {
        max-width: 600px;
        margin: 0 auto;
        background: white;
        padding: 30px;
        border-radius: 10px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }
    
    .status-radio {
        margin-right: 20px;
    }
    
    .status-radio label {
        margin-left: 5px;
        cursor: pointer;
    }
    
    .status-radio input[type="radio"] {
        cursor: pointer;
    }
    
    .status-present { color: #27ae60; }
    .status-absent { color: #e74c3c; }
    .status-late { color: #f39c12; }
    .status-excused { color: #3498db; }
    
    .info-box {
        background: #f8f9fa;
        padding: 10px 15px;
        border-radius: 8px;
        border-left: 4px solid #3498db;
    }
    
    @media (max-width: 768px) {
        .edit-content {
            margin-left: 0;
            padding: 15px;
        }
        
        .form-container {
            padding: 20px;
        }
    }
</style>

<div class="edit-content">
    <div class="container-fluid">
        
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4><i class="fas fa-edit"></i> Edit Attendance</h4>
            <a href="index.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to List
            </a>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <div class="form-container">
            <form method="POST" action="">
                <div class="mb-3">
                    <label class="form-label">Student</label>
                    <div class="info-box">
                        <strong><?= htmlspecialchars($attendance['student_id']) ?></strong>
                    </div>
                </div>
                
                <div class="mb-3">
                    <label class="form-label">Course</label>
                    <div class="info-box">
                        <strong><?= htmlspecialchars($attendance['course_id']) ?></strong>
                    </div>
                </div>
                
                <div class="mb-3">
                    <label class="form-label">Date</label>
                    <div class="info-box">
                        <strong><?= date('d M Y', strtotime($attendance['date'])) ?></strong>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Status <span class="text-danger">*</span></label>
                    <div class="d-flex flex-wrap">
                        <div class="status-radio">
                            <input type="radio" name="status" value="present" id="present" 
                                   <?= $attendance['status'] == 'present' ? 'checked' : '' ?>>
                            <label for="present" class="status-present">
                                <i class="fas fa-check-circle"></i> Present
                            </label>
                        </div>
                        <div class="status-radio">
                            <input type="radio" name="status" value="absent" id="absent" 
                                   <?= $attendance['status'] == 'absent' ? 'checked' : '' ?>>
                            <label for="absent" class="status-absent">
                                <i class="fas fa-times-circle"></i> Absent
                            </label>
                        </div>
                        <div class="status-radio">
                            <input type="radio" name="status" value="late" id="late" 
                                   <?= $attendance['status'] == 'late' ? 'checked' : '' ?>>
                            <label for="late" class="status-late">
                                <i class="fas fa-clock"></i> Late
                            </label>
                        </div>
                        <div class="status-radio">
                            <input type="radio" name="status" value="excused" id="excused" 
                                   <?= $attendance['status'] == 'excused' ? 'checked' : '' ?>>
                            <label for="excused" class="status-excused">
                                <i class="fas fa-check-circle"></i> Excused
                            </label>
                        </div>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Remark</label>
                    <textarea name="remark" class="form-control" rows="2" 
                              placeholder="Add a remark (optional)"><?= htmlspecialchars($attendance['remark'] ?? '') ?></textarea>
                </div>

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Update
                    </button>
                    <a href="index.php" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
        
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>