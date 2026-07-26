<?php
// fee_management/structure_add.php - Add Fee Structure (COMPLETE WORKING)

require_once __DIR__ . '../../../config/db_connect.php';
require_once __DIR__ . '/../includes/auth.php';

if (!isLoggedIn()) {
    header('Location: ' . BASE_URL . 'login.php');
    exit;
}

$user = getCurrentUser();
$role = $user['role_name'] ?? 'User';

if (!in_array($role, ['sso', 'admin'])) {
    header('Location: ' . BASE_URL . 'dashboard.php');
    exit;
}

$conn = getConnection();
$message = '';
$message_type = '';

// Get programs
$programs = [];
$prog_result = mysqli_query($conn, "SELECT program_id, program_name FROM programs WHERE status = 'Active' ORDER BY program_name");
if ($prog_result) {
    while ($row = mysqli_fetch_assoc($prog_result)) {
        $programs[] = $row;
    }
}

// Get sessions
$sessions = [];
$ses_result = mysqli_query($conn, "SELECT session_id, session_name FROM sessions WHERE status = 'Active' ORDER BY session_name");
if ($ses_result) {
    while ($row = mysqli_fetch_assoc($ses_result)) {
        $sessions[] = $row;
    }
}

// Get semesters
$semesters = [];
$sem_result = mysqli_query($conn, "SELECT semester_id, semester_name FROM semesters ORDER BY semester_name");
if ($sem_result) {
    while ($row = mysqli_fetch_assoc($sem_result)) {
        $semesters[] = $row;
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $structure_name = mysqli_real_escape_string($conn, $_POST['structure_name']);
    
    // Check if universal fee
    $is_universal = isset($_POST['is_universal']) ? 1 : 0;
    
    if ($is_universal == 1) {
        // Universal fee - no program/session/semester needed
        $program_id = 'NULL';
        $session_id = 'NULL';
        $semester_id = 'NULL';
    } else {
        // Regular fee - get values from POST
        $program_id = isset($_POST['program_id']) && !empty($_POST['program_id']) ? (int)$_POST['program_id'] : 'NULL';
        $session_id = isset($_POST['session_id']) && !empty($_POST['session_id']) ? (int)$_POST['session_id'] : 'NULL';
        $semester_id = isset($_POST['semester_id']) && !empty($_POST['semester_id']) ? (int)$_POST['semester_id'] : 'NULL';
    }
    
    $total_amount = floatval($_POST['total_amount']);
    $status = $_POST['status'] ?? 'Active';
    $fee_type = $_POST['fee_type'] ?? 'General';
    
    // Check if exists (only for non-universal)
    $error = false;
    if ($is_universal == 0) {
        $check_query = "SELECT fee_structure_id FROM fee_structures 
                        WHERE program_id = $program_id AND session_id = $session_id AND semester_id = $semester_id";
        $check_result = mysqli_query($conn, $check_query);
        
        if ($check_result && mysqli_num_rows($check_result) > 0) {
            $message = "Fee structure already exists for this Program, Session, and Semester!";
            $message_type = 'danger';
            $error = true;
        }
    }
    
    if (!$error) {
        // Insert with all columns
        $query = "INSERT INTO fee_structures (structure_name, program_id, session_id, semester_id, total_amount, status, fee_type, is_universal) 
                  VALUES ('$structure_name', $program_id, $session_id, $semester_id, $total_amount, '$status', '$fee_type', $is_universal)";
        
        if (mysqli_query($conn, $query)) {
            header('Location: index.php?tab=structures&success=1');
            exit;
        } else {
            $message = "Error: " . mysqli_error($conn);
            $message_type = 'danger';
        }
    }
}

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>

<style>
    .fee-content { margin-left: 250px; padding: 20px; }
    .card { border: none; border-radius: 15px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
    .card-header { background: white; border-bottom: 1px solid #eee; padding: 15px 20px; border-radius: 15px 15px 0 0; font-weight: 600; }
    .btn-save { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border: none; color: white; padding: 10px 30px; border-radius: 10px; }
    .btn-save:hover { transform: translateY(-2px); box-shadow: 0 5px 20px rgba(102, 126, 234, 0.4); color: white; }
    .sidebar { width: 250px; height: 100vh; background: linear-gradient(180deg, #1a1a2e 0%, #16213e 100%); color: white; position: fixed; left: 0; top: 0; overflow-y: auto; z-index: 1000; }
    .sidebar .brand { padding: 20px; text-align: center; border-bottom: 1px solid rgba(255,255,255,0.1); }
    .sidebar .brand h4 { font-weight: 700; margin: 0; }
    .sidebar .brand small { color: #a8a8b3; }
    .sidebar .nav-link { color: #a8a8b3; padding: 12px 20px; border-radius: 0; transition: all 0.3s; }
    .sidebar .nav-link:hover { color: white; background: rgba(255,255,255,0.05); }
    .sidebar .nav-link.active { color: white; background: rgba(102, 126, 234, 0.3); border-left: 3px solid #667eea; }
    .sidebar .nav-link i { width: 20px; margin-right: 10px; }
    .topbar { background: white; padding: 15px 25px; border-radius: 15px; margin-bottom: 20px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); display: flex; justify-content: space-between; align-items: center; }
    .topbar .avatar { width: 40px; height: 40px; border-radius: 50%; background: #667eea; color: white; display: flex; align-items: center; justify-content: center; font-weight: 700; }
    .hidden-field { display: none; }
    @media (max-width: 768px) { .sidebar { width: 100%; height: auto; position: relative; } .fee-content { margin-left: 0; } }
</style>

<div class="fee-content">
    <div class="container-fluid">
        <div class="topbar">
            <div><h5 class="mb-0"><i class="fas fa-plus text-primary"></i> Add Fee Structure</h5></div>
            <div class="avatar"><?php echo strtoupper(substr($user['full_name'] ?? 'U', 0, 2)); ?></div>
        </div>

        <?php if ($message): ?>
        <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show">
            <?php echo $message; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <div class="card">
            <div class="card-header"><i class="fas fa-file-invoice me-2"></i> Create New Fee Structure</div>
            <div class="card-body">
                <form method="POST">
                    <div class="row">
                        <!-- Structure Name -->
                        <div class="col-md-6 mb-3">
                            <label class="fw-semibold">Structure Name</label>
                            <input type="text" name="structure_name" class="form-control" placeholder="e.g., BS CS Fall 2024 Fee" required>
                        </div>

                        <!-- Fee Type -->
                        <div class="col-md-6 mb-3">
                            <label class="fw-semibold">Fee Type</label>
                            <select name="fee_type" class="form-select">
                                <option value="General">General</option>
                                <option value="Tuition">Tuition</option>
                                <option value="Lab">Lab</option>
                                <option value="Sports">Sports</option>
                                <option value="Library">Library</option>
                                <option value="Exam">Exam</option>
                                <option value="Registration">Registration</option>
                            </select>
                        </div>

                        <!-- Universal Fee Checkbox -->
                        <div class="col-md-12 mb-3">
                            <div class="form-check">
                                <input type="checkbox" name="is_universal" id="is_universal" class="form-check-input" value="1">
                                <label class="form-check-label" for="is_universal">
                                    <strong>Universal Fee</strong> <span class="text-muted">(Apply to all programs/semesters)</span>
                                </label>
                            </div>
                            <small class="text-muted">Check this if this fee applies to ALL students regardless of program or semester</small>
                        </div>

                        <!-- Program (Hidden for Universal) -->
                        <div class="col-md-6 mb-3" id="program_field">
                            <label class="fw-semibold">Program</label>
                            <select name="program_id" class="form-select">
                                <option value="">Select Program</option>
                                <?php foreach ($programs as $program): ?>
                                <option value="<?php echo $program['program_id']; ?>"><?php echo htmlspecialchars($program['program_name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Session (Hidden for Universal) -->
                        <div class="col-md-6 mb-3" id="session_field">
                            <label class="fw-semibold">Session</label>
                            <select name="session_id" class="form-select">
                                <option value="">Select Session</option>
                                <?php foreach ($sessions as $session): ?>
                                <option value="<?php echo $session['session_id']; ?>"><?php echo htmlspecialchars($session['session_name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Semester (Hidden for Universal) -->
                        <div class="col-md-6 mb-3" id="semester_field">
                            <label class="fw-semibold">Semester</label>
                            <select name="semester_id" class="form-select">
                                <option value="">Select Semester</option>
                                <?php foreach ($semesters as $semester): ?>
                                <option value="<?php echo $semester['semester_id']; ?>"><?php echo htmlspecialchars($semester['semester_name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Total Amount -->
                        <div class="col-md-6 mb-3">
                            <label class="fw-semibold">Total Amount</label>
                            <div class="input-group">
                                <span class="input-group-text">Rs.</span>
                                <input type="number" name="total_amount" class="form-control" placeholder="0.00" step="0.01" required>
                            </div>
                        </div>

                        <!-- Status -->
                        <div class="col-md-6 mb-3">
                            <label class="fw-semibold">Status</label>
                            <select name="status" class="form-select">
                                <option value="Active">Active</option>
                                <option value="Inactive">Inactive</option>
                            </select>
                        </div>

                        <!-- Submit Buttons -->
                        <div class="col-md-12 text-center">
                            <hr>
                            <button type="submit" class="btn btn-save"><i class="fas fa-save me-2"></i> Save</button>
                            <a href="index.php?tab=structures" class="btn btn-secondary"><i class="fas fa-times me-2"></i> Cancel</a>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- JavaScript to hide/show fields -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const universalCheckbox = document.getElementById('is_universal');
    const programField = document.getElementById('program_field');
    const sessionField = document.getElementById('session_field');
    const semesterField = document.getElementById('semester_field');
    
    function toggleFields() {
        if (universalCheckbox.checked) {
            programField.style.display = 'none';
            sessionField.style.display = 'none';
            semesterField.style.display = 'none';
            // Make selects not required
            document.querySelectorAll('#program_field select, #session_field select, #semester_field select').forEach(el => {
                el.removeAttribute('required');
            });
        } else {
            programField.style.display = 'block';
            sessionField.style.display = 'block';
            semesterField.style.display = 'block';
        }
    }
    
    universalCheckbox.addEventListener('change', toggleFields);
    toggleFields(); // Run on page load
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>