<?php
require_once '../../../../config/db_connect.php';
require_once '../models/StudentPromotion.php';
include '../../includes/header.php';
$hideSidebarToggle = true;
$showDashboardBackButton = true;
include '../../includes/navbar.php';

$promotion = new StudentPromotion();
$conn = getConnection();

// Get programs
$programs = $conn->query("SELECT program_id, program_name FROM programs ORDER BY program_name");

$students = [];
$selected_program = null;
$selected_semester = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['program_id']) && isset($_POST['semester'])) {
    $selected_program = $_POST['program_id'];
    $selected_semester = $_POST['semester'];
    $students = $promotion->getEligibleStudents($selected_program, $selected_semester);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['promote_students'])) {
    $student_ids = $_POST['student_ids'] ?? [];
    $next_semester = $_POST['next_semester'];
    
    if (!empty($student_ids)) {
        if ($promotion->promote($student_ids, $next_semester)) {
            $_SESSION['success'] = count($student_ids) . " students promoted to Semester " . $next_semester;
            header("Location: index.php");
            exit();
        } else {
            $_SESSION['error'] = "Failed to promote students!";
        }
    } else {
        $_SESSION['error'] = "Please select at least one student to promote!";
    }
}
?>

<div class="container-fluid mt-4">
    <h2>Student Promotion</h2>
    
    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <?php 
            echo $_SESSION['success'];
            unset($_SESSION['success']);
            ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    
    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <?php 
            echo $_SESSION['error'];
            unset($_SESSION['error']);
            ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    
    <div class="card">
        <div class="card-body">
            <form method="POST" class="row g-3">
                <div class="col-md-4">
                    <label for="program_id" class="form-label">Program</label>
                    <select class="form-select" id="program_id" name="program_id" required>
                        <option value="">Select Program</option>
                        <?php while($program = $programs->fetch_assoc()): ?>
                            <option value="<?php echo $program['program_id']; ?>" 
                                    <?php echo ($selected_program == $program['program_id']) ? 'selected' : ''; ?>>
                                <?php echo $program['program_name']; ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label for="semester" class="form-label">Current Semester</label>
                    <select class="form-select" id="semester" name="semester" required>
                        <option value="">Select Semester</option>
                        <?php for($i = 1; $i <= 8; $i++): ?>
                            <option value="<?php echo $i; ?>" <?php echo ($selected_semester == $i) ? 'selected' : ''; ?>>
                                Semester <?php echo $i; ?>
                            </option>
                        <?php endfor; ?>
                    </select>
                </div>
                <div class="col-md-4 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bi bi-search"></i> Check Eligibility
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <?php if (!empty($students)): ?>
    <div class="card mt-4">
        <div class="card-header">
            <div class="d-flex justify-content-between align-items-center">
                <h5>Eligible Students</h5>
                <span class="badge bg-success"><?php echo count($students); ?> students found</span>
            </div>
        </div>
        <div class="card-body">
            <form method="POST">
                <input type="hidden" name="program_id" value="<?php echo $selected_program; ?>">
                <input type="hidden" name="semester" value="<?php echo $selected_semester; ?>">
                <input type="hidden" name="next_semester" value="<?php echo $selected_semester + 1; ?>">
                
                <div class="mb-3">
                    <button type="button" class="btn btn-sm btn-outline-secondary" onclick="selectAll()">
                        <i class="bi bi-check-all"></i> Select All
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-secondary" onclick="deselectAll()">
                        <i class="bi bi-x-circle"></i> Deselect All
                    </button>
                </div>
                
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th><input type="checkbox" id="selectAllCheckbox" onchange="toggleAllCheckboxes()"></th>
                                <th>Student ID</th>
                                <th>Name</th>
                                <th>Current Semester</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($students as $student): ?>
                            <tr>
                                <td>
                                    <input type="checkbox" name="student_ids[]" 
                                           value="<?php echo $student['student_id']; ?>" 
                                           class="student-checkbox">
                                </td>
                                <td><?php echo $student['student_id']; ?></td>
                                <td><?php echo $student['full_name']; ?></td>
                                <td>Semester <?php echo $student['semester']; ?></td>
                                <td>
                                    <span class="badge bg-success">Eligible</span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <div class="alert alert-info mt-3">
                    <i class="bi bi-info-circle"></i> 
                    Students will be promoted to <strong>Semester <?php echo $selected_semester + 1; ?></strong>
                </div>
                
                <div class="d-flex justify-content-end mt-3">
                    <button type="submit" name="promote_students" class="btn btn-success btn-lg"
                            onclick="return confirm('Are you sure you want to promote selected students?')">
                        <i class="bi bi-arrow-up-circle"></i> Promote Selected Students
                    </button>
                </div>
            </form>
        </div>
    </div>
    <?php elseif ($_SERVER['REQUEST_METHOD'] === 'POST'): ?>
    <div class="alert alert-info mt-4">
        <i class="bi bi-info-circle"></i> 
        No students found eligible for promotion in this program and semester.
    </div>
    <?php endif; ?>
</div>

<script>
function toggleAllCheckboxes() {
    const selectAll = document.getElementById('selectAllCheckbox');
    const checkboxes = document.querySelectorAll('.student-checkbox');
    checkboxes.forEach(cb => cb.checked = selectAll.checked);
}

function selectAll() {
    const checkboxes = document.querySelectorAll('.student-checkbox');
    checkboxes.forEach(cb => cb.checked = true);
    document.getElementById('selectAllCheckbox').checked = true;
}

function deselectAll() {
    const checkboxes = document.querySelectorAll('.student-checkbox');
    checkboxes.forEach(cb => cb.checked = false);
    document.getElementById('selectAllCheckbox').checked = false;
}
</script>

<?php include '../../includes/footer.php'; ?>
