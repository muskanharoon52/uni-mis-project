<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
requireLogin();

$conn = getConnection();
$error = '';
$success = '';

// Fetch dropdown data
$programs = $conn->query("SELECT program_id, program_name, program_code FROM programs WHERE status = 'Active' ORDER BY program_name");
$semesters = $conn->query("SELECT semester_id, semester_name FROM semesters ORDER BY semester_name");
$sessions = $conn->query("SELECT session_id, session_name FROM sessions WHERE status = 'Active' ORDER BY session_name");

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $section_name = trim($_POST['section_name'] ?? '');
    $program_id = (int)$_POST['program_id'] ?? 0;
    $semester_id = (int)$_POST['semester_id'] ?? 0;
    $session_id = (int)$_POST['session_id'] ?? 0;
    $capacity = (int)$_POST['capacity'] ?? 30;
    $status = $_POST['status'] ?? 'Active';

    // Validation
    if (empty($section_name)) $error = "Please enter section name";
    elseif ($program_id <= 0) $error = "Please select a program";
    elseif ($semester_id <= 0) $error = "Please select a semester";
    elseif ($session_id <= 0) $error = "Please select a session";

    if (empty($error)) {
        // Check if section already exists
        $check_query = "SELECT section_id FROM sections 
                        WHERE section_name = ? AND program_id = ? AND semester_id = ? AND session_id = ?";
        $check_stmt = $conn->prepare($check_query);
        $check_stmt->bind_param("siii", $section_name, $program_id, $semester_id, $session_id);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();

        if ($check_result->num_rows > 0) {
            $error = "This section already exists for this program, semester, and session!";
        } else {
            $insert_query = "INSERT INTO sections (section_name, program_id, semester_id, session_id, capacity, status) 
                            VALUES (?, ?, ?, ?, ?, ?)";
            $insert_stmt = $conn->prepare($insert_query);
            $insert_stmt->bind_param("siiiss", $section_name, $program_id, $semester_id, $session_id, $capacity, $status);
            
            if ($insert_stmt->execute()) {
                header("Location: index.php?success=Section created successfully!");
                exit;
            } else {
                $error = "Error creating section: " . $conn->error;
            }
            $insert_stmt->close();
        }
        $check_stmt->close();
    }
}

// ============================================
// HEADER INCLUDE
// ============================================
require_once __DIR__ . '/../includes/header.php';
$page_title = 'Create Section';
include __DIR__ . '/../includes/sidebar.php';
?>

<style>
    .form-content {
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
    
    .required-star {
        color: #e74c3c;
        margin-left: 3px;
    }
    
    @media (max-width: 768px) {
        .form-content {
            margin-left: 0;
            padding: 15px;
        }
        
        .form-container {
            padding: 20px;
        }
    }
</style>

<div class="form-content">
    <div class="container-fluid">
        
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4><i class="fas fa-plus-circle"></i> Create Section</h4>
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
                    <label class="form-label">Section Name <span class="required-star">*</span></label>
                    <input type="text" name="section_name" class="form-control" 
                           placeholder="e.g., A, B, C" required>
                    <small class="text-muted">Enter a single letter or number for the section</small>
                </div>

                <div class="mb-3">
                    <label class="form-label">Program <span class="required-star">*</span></label>
                    <select name="program_id" class="form-select" required>
                        <option value="">Select Program</option>
                        <?php while($row = $programs->fetch_assoc()): ?>
                            <option value="<?= $row['program_id'] ?>">
                                <?= htmlspecialchars($row['program_name'] . ' (' . $row['program_code'] . ')') ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label">Semester <span class="required-star">*</span></label>
                    <select name="semester_id" class="form-select" required>
                        <option value="">Select Semester</option>
                        <?php while($row = $semesters->fetch_assoc()): ?>
                            <option value="<?= $row['semester_id'] ?>">
                                <?= htmlspecialchars($row['semester_name']) ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label">Session <span class="required-star">*</span></label>
                    <select name="session_id" class="form-select" required>
                        <option value="">Select Session</option>
                        <?php while($row = $sessions->fetch_assoc()): ?>
                            <option value="<?= $row['session_id'] ?>">
                                <?= htmlspecialchars($row['session_name']) ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label">Capacity</label>
                    <input type="number" name="capacity" class="form-control" value="30" min="1" max="100">
                    <small class="text-muted">Maximum number of students allowed in this section</small>
                </div>

                <div class="mb-3">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        <option value="Active">Active</option>
                        <option value="Inactive">Inactive</option>
                    </select>
                </div>

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Create Section
                    </button>
                    <a href="index.php" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
        
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>