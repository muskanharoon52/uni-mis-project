<?php
// fee_management/structure_edit.php - Edit Fee Structure

require_once __DIR__ . '/../../../config/db_connect.php';
require_once __DIR__ . '/../includes/auth.php';

if (!isLoggedIn()) {
    header('Location: ' . BASE_URL . 'login.php');
    exit;
}

$user = getCurrentUser();
$role = strtolower($user['role_name'] ?? 'user');

if (!in_array($role, ['sso', 'admin'])) {
    header('Location: ' . BASE_URL . 'dashboard.php');
    exit;
}

$conn = getConnection();
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id == 0) {
    header('Location: index.php?tab=structures&error=Invalid ID');
    exit;
}

// Get fee structure
$query = "SELECT * FROM fee_structures WHERE fee_structure_id = $id";
$result = mysqli_query($conn, $query);
$structure = mysqli_fetch_assoc($result);

if (!$structure) {
    header('Location: index.php?tab=structures&error=Not found');
    exit;
}

// Get dropdowns
$programs = [];
$prog_result = mysqli_query($conn, "SELECT program_id, program_name FROM programs WHERE status = 'Active' ORDER BY program_name");
if ($prog_result) {
    while ($row = mysqli_fetch_assoc($prog_result)) {
        $programs[] = $row;
    }
}

$sessions = [];
$ses_result = mysqli_query($conn, "SELECT session_id, session_name FROM sessions WHERE status = 'Active' ORDER BY session_name");
if ($ses_result) {
    while ($row = mysqli_fetch_assoc($ses_result)) {
        $sessions[] = $row;
    }
}

$semesters = [];
$sem_result = mysqli_query($conn, "SELECT semester_id, semester_name FROM semesters ORDER BY semester_name");
if ($sem_result) {
    while ($row = mysqli_fetch_assoc($sem_result)) {
        $semesters[] = $row;
    }
}

// Handle update
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $structure_name = mysqli_real_escape_string($conn, $_POST['structure_name']);
    $program_id = (int)$_POST['program_id'];
    $session_id = (int)$_POST['session_id'];
    $semester_id = (int)$_POST['semester_id'];
    $total_amount = floatval($_POST['total_amount']);
    $status = $_POST['status'] ?? 'Active';
    
    $update_query = "UPDATE fee_structures SET 
                      structure_name = '$structure_name',
                      program_id = $program_id,
                      session_id = $session_id,
                      semester_id = $semester_id,
                      total_amount = $total_amount,
                      status = '$status'
                    WHERE fee_structure_id = $id";
    
    if (mysqli_query($conn, $update_query)) {
        header('Location: index.php?tab=structures&updated=1');
        exit;
    } else {
        $error = mysqli_error($conn);
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
            <div><h5 class="mb-0"><i class="fas fa-edit text-warning"></i> Edit Fee Structure</h5></div>
            <div class="avatar"><?php echo strtoupper(substr($user['full_name'] ?? 'U', 0, 2)); ?></div>
        </div>

        <?php if (isset($error)): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>

        <div class="card">
            <div class="card-header"><i class="fas fa-file-invoice me-2"></i> Update Fee Structure</div>
            <div class="card-body">
                <form method="POST">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="fw-semibold">Structure Name</label>
                            <input type="text" name="structure_name" class="form-control" value="<?php echo htmlspecialchars($structure['structure_name']); ?>" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="fw-semibold">Program</label>
                            <select name="program_id" class="form-select" required>
                                <option value="">Select Program</option>
                                <?php foreach ($programs as $program): ?>
                                <option value="<?php echo $program['program_id']; ?>" <?php echo ($structure['program_id'] == $program['program_id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($program['program_name']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="fw-semibold">Session</label>
                            <select name="session_id" class="form-select" required>
                                <option value="">Select Session</option>
                                <?php foreach ($sessions as $session): ?>
                                <option value="<?php echo $session['session_id']; ?>" <?php echo ($structure['session_id'] == $session['session_id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($session['session_name']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="fw-semibold">Semester</label>
                            <select name="semester_id" class="form-select" required>
                                <option value="">Select Semester</option>
                                <?php foreach ($semesters as $semester): ?>
                                <option value="<?php echo $semester['semester_id']; ?>" <?php echo ($structure['semester_id'] == $semester['semester_id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($semester['semester_name']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="fw-semibold">Total Amount</label>
                            <div class="input-group">
                                <span class="input-group-text">Rs.</span>
                                <input type="number" name="total_amount" class="form-control" value="<?php echo $structure['total_amount']; ?>" step="0.01" required>
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="fw-semibold">Status</label>
                            <select name="status" class="form-select">
                                <option value="Active" <?php echo ($structure['status'] == 'Active') ? 'selected' : ''; ?>>Active</option>
                                <option value="Inactive" <?php echo ($structure['status'] == 'Inactive') ? 'selected' : ''; ?>>Inactive</option>
                            </select>
                        </div>
                        <div class="col-md-12 text-center">
                            <hr>
                            <button type="submit" class="btn btn-save"><i class="fas fa-save me-2"></i> Update</button>
                            <a href="index.php?tab=structures" class="btn btn-secondary"><i class="fas fa-times me-2"></i> Cancel</a>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>