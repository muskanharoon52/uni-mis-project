<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
requireLogin();

$conn = getConnection();
$error = '';
$success = '';
$edit_id = isset($_GET['edit']) ? (int)$_GET['edit'] : 0;
$fee_data = null;

// If editing, fetch existing data
if ($edit_id > 0) {
    $query = "SELECT * FROM course_fees WHERE fee_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $edit_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $fee_data = $result->fetch_assoc();
    $stmt->close();
    
    if (!$fee_data) {
        header("Location: index.php?error=Fee record not found");
        exit;
    }
}

// Fetch courses and semesters for dropdowns
$courses = $conn->query("SELECT course_id, course_code, course_name, credit_hours FROM courses ORDER BY course_code");
$semesters = $conn->query("SELECT semester_id, semester_name FROM semesters ORDER BY semester_name");

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $course_id = (int)$_POST['course_id'];
    $semester_id = (int)$_POST['semester_id'];
    $fee_amount = (float)$_POST['fee_amount'];
    $fee_type = $_POST['fee_type'] ?? 'Fixed';
    $description = trim($_POST['description'] ?? '');
    $is_active = isset($_POST['is_active']) ? 1 : 0;

    // Validation
    if ($course_id <= 0) $error = "Please select a course";
    elseif ($semester_id <= 0) $error = "Please select a semester";
    elseif ($fee_amount <= 0) $error = "Please enter a valid fee amount";

    if (empty($error)) {
        if ($edit_id > 0) {
            // Update existing
            $update_query = "UPDATE course_fees SET 
                             course_id = ?, 
                             semester_id = ?, 
                             fee_amount = ?, 
                             fee_type = ?, 
                             description = ?, 
                             is_active = ? 
                             WHERE fee_id = ?";
            $stmt = $conn->prepare($update_query);
            $stmt->bind_param("iidssii", $course_id, $semester_id, $fee_amount, $fee_type, $description, $is_active, $edit_id);
        } else {
            // Insert new
            $insert_query = "INSERT INTO course_fees 
                            (course_id, semester_id, fee_amount, fee_type, description, is_active) 
                            VALUES (?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($insert_query);
            $stmt->bind_param("iidssi", $course_id, $semester_id, $fee_amount, $fee_type, $description, $is_active);
        }
        
        if ($stmt->execute()) {
            header("Location: index.php?success=Fee " . ($edit_id > 0 ? "updated" : "configured") . " successfully!");
            exit;
        } else {
            $error = "Error saving fee: " . $conn->error;
        }
        $stmt->close();
    }
}

// ============================================
// HEADER INCLUDE
// ============================================
require_once __DIR__ . '/../includes/header.php';
$page_title = $edit_id > 0 ? 'Edit Course Fee' : 'Set Course Fee';
include __DIR__ . '/../includes/sidebar.php';
?>

<style>
    .fee-form-content {
        margin-left: 250px;
        padding: 20px;
        min-height: 100vh;
        background: #f5f6fa;
    }
    
    .form-container {
        max-width: 700px;
        margin: 0 auto;
        background: white;
        padding: 30px;
        border-radius: 10px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }
    
    .form-container .form-label {
        font-weight: 600;
        color: #2c3e50;
    }
    
    .required-star {
        color: #e74c3c;
        margin-left: 3px;
    }
    
    .fee-amount-display {
        font-size: 2rem;
        font-weight: 700;
        color: #27ae60;
    }
    
    .btn-submit {
        border-radius: 20px;
        padding: 10px 30px;
        font-weight: 600;
    }
    
    @media (max-width: 768px) {
        .fee-form-content {
            margin-left: 0;
            padding: 15px;
        }
        
        .form-container {
            padding: 20px;
        }
    }
</style>

<div class="fee-form-content">
    <div class="container-fluid">
        
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4><i class="fas fa-<?= $edit_id > 0 ? 'edit' : 'plus-circle' ?>"></i> 
                <?= $edit_id > 0 ? 'Edit' : 'Set' ?> Course Fee
            </h4>
            <a href="index.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to List
            </a>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <div class="form-container">
            <form method="POST" action="">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Course <span class="required-star">*</span></label>
                        <select name="course_id" class="form-select" required>
                            <option value="">Select Course</option>
                            <?php while($row = $courses->fetch_assoc()): ?>
                                <option value="<?= $row['course_id'] ?>" 
                                    <?= ($fee_data['course_id'] ?? 0) == $row['course_id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($row['course_code'] . ' - ' . $row['course_name']) ?>
                                    (<?= $row['credit_hours'] ?> Credits)
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Semester <span class="required-star">*</span></label>
                        <select name="semester_id" class="form-select" required>
                            <option value="">Select Semester</option>
                            <?php while($row = $semesters->fetch_assoc()): ?>
                                <option value="<?= $row['semester_id'] ?>" 
                                    <?= ($fee_data['semester_id'] ?? 0) == $row['semester_id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($row['semester_name']) ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Fee Amount (Rs.) <span class="required-star">*</span></label>
                        <input type="number" name="fee_amount" class="form-control" 
                               step="0.01" min="0" 
                               value="<?= number_format($fee_data['fee_amount'] ?? 0, 2) ?>" 
                               onchange="updateDisplay(this.value)" required>
                        <small class="text-muted">Enter the fee amount for this course</small>
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Fee Type <span class="required-star">*</span></label>
                        <select name="fee_type" class="form-select" required>
                            <option value="Fixed" <?= ($fee_data['fee_type'] ?? 'Fixed') == 'Fixed' ? 'selected' : '' ?>>Fixed</option>
                            <option value="Per Credit Hour" <?= ($fee_data['fee_type'] ?? '') == 'Per Credit Hour' ? 'selected' : '' ?>>Per Credit Hour</option>
                            <option value="Lab Fee" <?= ($fee_data['fee_type'] ?? '') == 'Lab Fee' ? 'selected' : '' ?>>Lab Fee</option>
                            <option value="Exam Fee" <?= ($fee_data['fee_type'] ?? '') == 'Exam Fee' ? 'selected' : '' ?>>Exam Fee</option>
                        </select>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Description</label>
                    <textarea name="description" class="form-control" rows="3" 
                              placeholder="Additional details about this fee..."><?= htmlspecialchars($fee_data['description'] ?? '') ?></textarea>
                </div>

                <div class="mb-3">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="is_active" id="is_active" 
                               <?= ($fee_data['is_active'] ?? 1) ? 'checked' : '' ?>>
                        <label class="form-check-label" for="is_active">
                            Active
                        </label>
                        <br>
                        <small class="text-muted">Inactive fees will not be applied to new student registrations</small>
                    </div>
                </div>

                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i> 
                    <?php if ($edit_id > 0): ?>
                        Updating this fee will affect all existing student fee records for this course and semester.
                    <?php else: ?>
                        This fee will be applied to all students enrolled in this course for the selected semester.
                    <?php endif; ?>
                </div>

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary btn-submit">
                        <i class="fas fa-save"></i> <?= $edit_id > 0 ? 'Update' : 'Save' ?> Fee
                    </button>
                    <a href="index.php" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
        
    </div>
</div>

<script>
    function updateDisplay(value) {
        // Auto-format the fee display
        const formatted = parseFloat(value).toFixed(2);
        // You can update a display element here if needed
    }
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>