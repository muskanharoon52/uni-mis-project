<?php
require_once __DIR__ . '/../config/db_connect.php';
require_once __DIR__ . '/../modules/sso/includes/auth.php';

if (!isLoggedIn()) {
    header('Location: /uni-mis-project/');
    exit;
}

global $conn;

$error = '';
$success = '';

// Departments (majors)
$departments = [];
$res = mysqli_query($conn, "SELECT department_id, department_name FROM departments WHERE status = 'Active' ORDER BY department_name");
if ($res) { while ($row = mysqli_fetch_assoc($res)) { $departments[] = $row; } }

$edit_teacher = null;

// =============================================
// HANDLE POST
// =============================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $teacher_id = isset($_POST['teacher_id']) ? (int)$_POST['teacher_id'] : 0;

    if ($action === 'save') {
        $teacher_name = trim($_POST['teacher_name'] ?? '');
        $designation = trim($_POST['designation'] ?? '');
        $salary = isset($_POST['salary']) && $_POST['salary'] !== '' ? (float)$_POST['salary'] : null;
        $department_id = (int)($_POST['department_id'] ?? 0);
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $status = $_POST['status'] ?? 'Active';

        if (empty($teacher_name)) {
            $error = "Teacher name is required.";
        } elseif ($department_id <= 0) {
            $error = "Please select a major (department).";
        } elseif (empty($email)) {
            $error = "Email is required.";
        } else {
            if ($teacher_id > 0) {
                $stmt = mysqli_prepare($conn, "UPDATE teachers SET teacher_name=?, designation=?, salary=?, department_id=?, email=?, phone=?, status=? WHERE teacher_id=?");
                mysqli_stmt_bind_param($stmt, 'ssdisssi', $teacher_name, $designation, $salary, $department_id, $email, $phone, $status, $teacher_id);
                if (mysqli_stmt_execute($stmt)) {
                    $success = "Teacher #$teacher_id updated successfully.";
                } else {
                    $error = "Error updating teacher: " . mysqli_stmt_error($stmt);
                }
                mysqli_stmt_close($stmt);
            } else {
                $stmt = mysqli_prepare($conn, "INSERT INTO teachers (teacher_name, designation, salary, department_id, email, phone, status) VALUES (?, ?, ?, ?, ?, ?, ?)");
                mysqli_stmt_bind_param($stmt, 'ssdisss', $teacher_name, $designation, $salary, $department_id, $email, $phone, $status);
                if (mysqli_stmt_execute($stmt)) {
                    $new_id = mysqli_insert_id($conn);
                    $success = "Teacher added successfully. Teacher ID: $new_id";
                } else {
                    $error = "Error adding teacher: " . mysqli_stmt_error($stmt);
                }
                mysqli_stmt_close($stmt);
            }
        }
    } elseif ($action === 'delete') {
        if ($teacher_id > 0) {
            mysqli_query($conn, "DELETE FROM teacher_courses WHERE teacher_id = $teacher_id");
            if (mysqli_query($conn, "DELETE FROM teachers WHERE teacher_id = $teacher_id")) {
                $success = "Teacher #$teacher_id deleted.";
            } else {
                $error = "Error deleting teacher: " . mysqli_error($conn);
            }
        }
    }
}

// Edit request
if (isset($_GET['edit'])) {
    $eid = (int)$_GET['edit'];
    $rq = mysqli_query($conn, "SELECT * FROM teachers WHERE teacher_id = $eid");
    if ($rq && ($row = mysqli_fetch_assoc($rq))) {
        $edit_teacher = $row;
    }
}

// =============================================
// LIST TEACHERS
// =============================================
$teachers = [];
$sql = "SELECT t.*, d.department_name
        FROM teachers t
        LEFT JOIN departments d ON d.department_id = t.department_id
        ORDER BY t.teacher_id ASC";
$res = mysqli_query($conn, $sql);
if ($res) { while ($row = mysqli_fetch_assoc($res)) { $teachers[] = $row; } }

include __DIR__ . '/../includes/header.php';
?>

    <div class="container-fluid">
        <!-- Page Header -->
        <div class="page-header">
            <h2><i class="fas fa-id-card"></i> Faculty Registry</h2>
            <div class="btn-group">
                <span class="badge bg-primary" style="align-self:center;"><?= count($teachers) ?> registered teacher(s)</span>
            </div>
        </div>

        <?php if ($error): ?><div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?></div><?php endif; ?>
        <?php if ($success): ?><div class="alert alert-success"><i class="fas fa-check-circle"></i> <?= $success ?></div><?php endif; ?>

        <!-- Add / Edit Teacher Form -->
        <div class="panel mt-3">
            <h5 class="mb-3"><?= $edit_teacher ? 'Edit Teacher #' . (int)$edit_teacher['teacher_id'] : 'Add New Teacher' ?></h5>
            <form method="POST" class="row g-3">
                <input type="hidden" name="action" value="save">
                <?php if ($edit_teacher): ?>
                    <input type="hidden" name="teacher_id" value="<?= (int)$edit_teacher['teacher_id']; ?>">
                <?php endif; ?>

                <div class="col-md-4">
                    <label class="form-label fw-semibold small text-muted">Teacher Name <span class="text-danger">*</span></label>
                    <input type="text" name="teacher_name" class="form-control" required
                           value="<?= htmlspecialchars($edit_teacher['teacher_name'] ?? ''); ?>" placeholder="e.g. Dr. Sara Khan">
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-semibold small text-muted">Designation</label>
                    <input type="text" name="designation" class="form-control"
                           value="<?= htmlspecialchars($edit_teacher['designation'] ?? ''); ?>" placeholder="e.g. Professor, Lecturer">
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-semibold small text-muted">Salary (PKR)</label>
                    <input type="number" name="salary" class="form-control" min="0" step="0.01"
                           value="<?= htmlspecialchars($edit_teacher['salary'] ?? ''); ?>" placeholder="e.g. 150000">
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-semibold small text-muted">Major (Department) <span class="text-danger">*</span></label>
                    <select name="department_id" class="form-select" required>
                        <option value="0">Select Major</option>
                        <?php foreach ($departments as $d): ?>
                            <option value="<?= $d['department_id']; ?>" <?= ($edit_teacher && (int)$edit_teacher['department_id'] === (int)$d['department_id']) ? 'selected' : ''; ?>>
                                <?= htmlspecialchars($d['department_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-semibold small text-muted">Email <span class="text-danger">*</span></label>
                    <input type="email" name="email" class="form-control" required
                           value="<?= htmlspecialchars($edit_teacher['email'] ?? ''); ?>" placeholder="teacher@university.edu">
                </div>
                <div class="col-md-2">
                    <label class="form-label fw-semibold small text-muted">Phone</label>
                    <input type="text" name="phone" class="form-control"
                           value="<?= htmlspecialchars($edit_teacher['phone'] ?? ''); ?>" placeholder="0300-...">
                </div>
                <div class="col-md-2">
                    <label class="form-label fw-semibold small text-muted">Status</label>
                    <select name="status" class="form-select">
                        <option value="Active" <?= (!$edit_teacher || $edit_teacher['status'] === 'Active') ? 'selected' : ''; ?>>Active</option>
                        <option value="Inactive" <?= ($edit_teacher && $edit_teacher['status'] === 'Inactive') ? 'selected' : ''; ?>>Inactive</option>
                    </select>
                </div>
                <div class="col-12">
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> <?= $edit_teacher ? 'Update Teacher' : 'Add Teacher' ?></button>
                    <?php if ($edit_teacher): ?>
                        <a href="index.php" class="btn btn-secondary"><i class="fas fa-times"></i> Cancel Edit</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>

        <!-- Teachers List -->
        <div class="card mt-3">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5>Registered Teachers</h5>
            </div>
            <div class="card-body">
                <?php if (!empty($teachers)): ?>
                    <div class="table-responsive">
                        <table class="table table-hover datatable">
                            <thead>
                                <tr>
                                    <th>Teacher ID</th>
                                    <th>Name</th>
                                    <th>Designation</th>
                                    <th>Major (Department)</th>
                                    <th>Salary</th>
                                    <th>Email</th>
                                    <th>Phone</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($teachers as $t): ?>
                                    <tr>
                                        <td style="font-weight:600;">T-<?= str_pad((int)$t['teacher_id'], 4, '0', STR_PAD_LEFT); ?></td>
                                        <td><?= htmlspecialchars($t['teacher_name']); ?></td>
                                        <td><?= htmlspecialchars($t['designation'] ?? 'N/A'); ?></td>
                                        <td>
                                            <span class="badge bg-info"><?= htmlspecialchars($t['department_name'] ?? 'N/A'); ?></span>
                                        </td>
                                        <td><?= $t['salary'] !== null ? 'Rs ' . number_format((float)$t['salary'], 0) : 'N/A'; ?></td>
                                        <td><?= htmlspecialchars($t['email']); ?></td>
                                        <td><?= htmlspecialchars($t['phone'] ?? 'N/A'); ?></td>
                                        <td>
                                            <span class="status-badge <?= $t['status'] === 'Active' ? 'status-active' : 'status-pending' ?>">
                                                <?= htmlspecialchars($t['status']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <a href="index.php?edit=<?= (int)$t['teacher_id']; ?>" class="btn btn-sm btn-outline-primary" title="Edit"><i class="fas fa-edit"></i></a>
                                            <form method="POST" style="display:inline-block;" onsubmit="return confirm('Delete teacher #<?= (int)$t['teacher_id']; ?>? Their course assignments will also be removed.');">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="teacher_id" value="<?= (int)$t['teacher_id']; ?>">
                                                <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete"><i class="fas fa-trash"></i></button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-id-card"></i>
                        <h5>No Teachers Registered</h5>
                        <p class="text-muted">Use the form above to add your first teacher.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
