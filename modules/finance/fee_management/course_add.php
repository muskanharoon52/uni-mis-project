<?php
// fee_management/course_add.php - Add Course Fee (SIMPLE - No Semester)

require_once __DIR__ . '/../config/db_connect.php';
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

// Get ALL courses
$courses = [];
$course_result = mysqli_query($conn, "SELECT course_id, course_code, course_name FROM courses ORDER BY course_code");
if ($course_result) {
    while ($row = mysqli_fetch_assoc($course_result)) {
        $courses[] = $row;
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $course_id = (int)$_POST['course_id'];
    $fee_amount = floatval($_POST['fee_amount']);
    $fee_type = $_POST['fee_type'] ?? 'Fixed';
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    
    // ✅ Check if course fee already exists
    $check_query = "SELECT fee_id FROM course_fees WHERE course_id = $course_id";
    $check_result = mysqli_query($conn, $check_query);
    
    if ($check_result && mysqli_num_rows($check_result) > 0) {
        $message = "Fee already exists for this course!";
        $message_type = 'danger';
    } else {
        // ✅ Simple INSERT - No semester_id
        $query = "INSERT INTO course_fees (course_id, fee_amount, fee_type, is_active) 
                  VALUES ($course_id, $fee_amount, '$fee_type', $is_active)";
        
        if (mysqli_query($conn, $query)) {
            header('Location: index.php?tab=course_fees&success=1');
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
    @media (max-width: 768px) { .sidebar { width: 100%; height: auto; position: relative; } .fee-content { margin-left: 0; } }
</style>

<div class="fee-content">
    <div class="container-fluid">
        <div class="topbar">
            <div><h5 class="mb-0"><i class="fas fa-plus text-success"></i> Add Course Fee</h5></div>
            <div class="avatar"><?php echo strtoupper(substr($user['full_name'] ?? 'U', 0, 2)); ?></div>
        </div>

        <?php if ($message): ?>
        <div class="alert alert-<?php echo $message_type; ?>"><?php echo $message; ?></div>
        <?php endif; ?>

        <div class="card">
            <div class="card-header"><i class="fas fa-book me-2"></i> Add Course Fee</div>
            <div class="card-body">
                <form method="POST">
                    <div class="row">
                        <div class="col-md-12 mb-3">
                            <label class="fw-semibold">Select Course</label>
                            <select name="course_id" class="form-select" required>
                                <option value="">-- Select Course --</option>
                                <?php foreach ($courses as $course): ?>
                                <option value="<?php echo $course['course_id']; ?>">
                                    <?php echo htmlspecialchars($course['course_code'] . ' - ' . $course['course_name']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="fw-semibold">Fee Amount</label>
                            <div class="input-group">
                                <span class="input-group-text">Rs.</span>
                                <input type="number" name="fee_amount" class="form-control" placeholder="0.00" step="0.01" required>
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="fw-semibold">Fee Type</label>
                            <select name="fee_type" class="form-select">
                                <option value="Fixed">Fixed</option>
                                <option value="Per Credit Hour">Per Credit Hour</option>
                                <option value="Lab Fee">Lab Fee</option>
                                <option value="Exam Fee">Exam Fee</option>
                            </select>
                        </div>
                        <div class="col-md-12 mb-3">
                            <div class="form-check">
                                <input type="checkbox" name="is_active" class="form-check-input" checked>
                                <label class="form-check-label">Active</label>
                            </div>
                        </div>
                        <div class="col-md-12 text-center">
                            <hr>
                            <button type="submit" class="btn btn-save"><i class="fas fa-save me-2"></i> Save</button>
                            <a href="index.php?tab=course_fees" class="btn btn-secondary"><i class="fas fa-times me-2"></i> Cancel</a>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>