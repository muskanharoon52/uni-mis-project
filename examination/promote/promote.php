<?php
$page_title = 'Promote Students';
require_once '../../config/db_connect.php';
require_once '../models/StudentPromotion.php';
include '../includes/header.php';
include '../includes/sidebar.php';

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

<div class="content-area" id="contentArea">
    <div class="page-header">
        <div class="page-header-left">
            <h4>Student Promotion</h4>
        </div>
    </div>
    
    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success">
            <?php 
            echo $_SESSION['success'];
            unset($_SESSION['success']);
            ?>
        </div>
    <?php endif; ?>
    
    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-error">
            <?php 
            echo $_SESSION['error'];
            unset($_SESSION['error']);
            ?>
        </div>
    <?php endif; ?>
    
    <div class="card">
        <div class="card-content">
            <div class="form-container">
                <form method="POST">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="program_id">Program</label>
                            <select id="program_id" name="program_id" required>
                                <option value="">Select Program</option>
                                <?php while($program = $programs->fetch_assoc()): ?>
                                    <option value="<?php echo $program['program_id']; ?>" 
                                            <?php echo ($selected_program == $program['program_id']) ? 'selected' : ''; ?>>
                                        <?php echo $program['program_name']; ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="semester">Current Semester</label>
                            <select id="semester" name="semester" required>
                                <option value="">Select Semester</option>
                                <?php for($i = 1; $i <= 8; $i++): ?>
                                    <option value="<?php echo $i; ?>" <?php echo ($selected_semester == $i) ? 'selected' : ''; ?>>
                                        Semester <?php echo $i; ?>
                                    </option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>&nbsp;</label>
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-search"></i> Check Eligibility
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <?php if (!empty($students)): ?>
    <div class="card">
        <div class="card-header">
            <div style="display:flex;justify-content:space-between;align-items:center;">
                <h5>Eligible Students</h5>
                <span class="status-badge" style="background:var(--success-bg);color:var(--success);border:1px solid var(--success-border);"><?php echo count($students); ?> students found</span>
            </div>
        </div>
        <div class="card-content">
            <form method="POST">
                <input type="hidden" name="program_id" value="<?php echo $selected_program; ?>">
                <input type="hidden" name="semester" value="<?php echo $selected_semester; ?>">
                <input type="hidden" name="next_semester" value="<?php echo $selected_semester + 1; ?>">
                
                <div style="margin-bottom:1rem;">
                    <button type="button" class="btn btn-outline" onclick="selectAll()">
                        <i class="bi bi-check-all"></i> Select All
                    </button>
                    <button type="button" class="btn btn-outline" onclick="deselectAll()">
                        <i class="bi bi-x-circle"></i> Deselect All
                    </button>
                </div>
                
                <div class="table-responsive">
                    <table class="data-table">
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
                                    <span class="status-badge" style="background:var(--success-bg);color:var(--success);border:1px solid var(--success-border);">Eligible</span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <div class="alert" style="background:var(--info-bg);color:var(--accent);border:1px solid var(--info-border);margin-top:1rem;">
                    <i class="bi bi-info-circle"></i> 
                    Students will be promoted to <strong>Semester <?php echo $selected_semester + 1; ?></strong>
                </div>
                
                <div class="form-actions">
                    <button type="submit" name="promote_students" class="btn btn-primary"
                            onclick="return confirm('Are you sure you want to promote selected students?')">
                        <i class="bi bi-arrow-up-circle"></i> Promote Selected Students
                    </button>
                </div>
            </form>
        </div>
    </div>
    <?php elseif ($_SERVER['REQUEST_METHOD'] === 'POST'): ?>
    <div class="alert" style="background:var(--info-bg);color:var(--accent);border:1px solid var(--info-border);">
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

<?php include '../includes/footer.php'; ?>
