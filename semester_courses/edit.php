<?php
require_once __DIR__ . '/../config/db_connect.php';
require_once __DIR__ . '/../includes/header.php';

$page_title = 'Assign Courses to Semester';
include __DIR__ . '/../includes/sidebar.php';

$course_id = isset($_GET['course_id']) ? intval($_GET['course_id']) : 0;
$program_id = isset($_GET['program_id']) ? intval($_GET['program_id']) : 0;
$semester_id = isset($_GET['semester_id']) ? intval($_GET['semester_id']) : 0;

$programs = getRows("SELECT * FROM departments ORDER BY name");
$semesters = getRows("SELECT * FROM semesters ORDER BY semester_code");
$courses = getRows("SELECT * FROM courses ORDER BY course_code");

if (empty($courses)) { echo '<div class="alert alert-error">No courses found in database. Please add courses first.</div>'; }

$programCourses = [];
if($program_id > 0) { $sql = "SELECT c.* FROM courses c WHERE c.department_id = $program_id ORDER BY c.course_code"; $programCourses = getRows($sql); }

$error = '';
$success_msg = '';

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $selected_program = isset($_POST['program_id']) ? intval($_POST['program_id']) : 0;
    $selected_semester = isset($_POST['semester_id']) ? intval($_POST['semester_id']) : 0;
    $selected_courses = isset($_POST['courses']) ? $_POST['courses'] : [];

    if($selected_program == 0 || $selected_semester == 0 || empty($selected_courses)) {
        $error = "Please select program, semester, and at least one course!";
    } else {
        $success = true;
        $assigned_count = 0;
        foreach($selected_courses as $course_id) {
            $check_sql = "SELECT * FROM semester_courses WHERE semester_id = ? AND course_id = ? AND program_id = ?";
            $check = executeQuery($check_sql, [$selected_semester, $course_id, $selected_program]);
            if ($check) {
                $result = $check->get_result();
                if($result->num_rows == 0) {
                    $insert_sql = "INSERT INTO semester_courses (semester_id, course_id, program_id, assigned_by, assigned_date) VALUES (?, ?, ?, ?, NOW())";
                    $insert = executeQuery($insert_sql, [$selected_semester, $course_id, $selected_program, $_SESSION['user_id'] ?? 1]);
                    if($insert) { $assigned_count++; }
                    else { $success = false; $error = "Error assigning courses: " . $conn->error; break; }
                }
            }
        }
        if($success && $assigned_count > 0) { $success_msg = "$assigned_count course(s) assigned successfully!"; }
        elseif($success && $assigned_count == 0) { $error = "All selected courses are already assigned to this semester!"; }
    }
}

$preselected_course = $course_id > 0 ? [$course_id] : [];
?>

<div class="page-header">
    <h4><i class="fas fa-tasks"></i> Assign Courses to Semester</h4>
    <div class="page-header-actions">
        <a href="index.php" class="btn btn-outline"><i class="fas fa-arrow-left"></i> Back to Courses</a>
    </div>
</div>

<?php if($error): ?><div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?></div><?php endif; ?>
<?php if($success_msg): ?><div class="alert alert-success"><i class="fas fa-check-circle"></i> <?= htmlspecialchars($success_msg) ?></div><?php endif; ?>

<form method="POST" action="" id="assignForm">
    <div class="form-container" style="margin-bottom:16px;">
        <div class="form-row">
            <div class="form-group">
                <label><i class="fas fa-university"></i> Select Program</label>
                <select name="program_id" id="program_id" required>
                    <option value="">-- Select Program --</option>
                    <?php foreach($programs as $program): ?>
                        <option value="<?= $program['id'] ?>" <?= ($program_id == $program['id']) ? 'selected' : '' ?>><?= htmlspecialchars($program['name']) ?> (<?= $program['code'] ?>)</option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label><i class="fas fa-calendar-alt"></i> Select Semester</label>
                <select name="semester_id" id="semester_id" required>
                    <option value="">-- Select Semester --</option>
                    <?php foreach($semesters as $semester): ?>
                        <option value="<?= $semester['id'] ?>" <?= ($semester_id == $semester['id']) ? 'selected' : '' ?>><?= htmlspecialchars($semester['semester_name']) ?> (<?= $semester['semester_code'] ?>)</option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <div class="form-actions" style="border-top:none;padding-top:0;margin-top:0;">
            <button type="submit" class="btn btn-primary" id="assignBtn"><i class="fas fa-save"></i> Assign Selected Courses</button>
        </div>
    </div>

    <?php $displayCourses = ($program_id > 0) ? $programCourses : $courses; if(!empty($displayCourses)): ?>
        <div class="card">
            <div class="card-header" style="display:flex;justify-content:space-between;align-items:center;">
                <h5><i class="fas fa-list"></i> Select Courses to Assign</h5>
                <div style="display:flex;gap:6px;">
                    <button type="button" class="btn btn-ghost btn-sm" id="selectAll"><i class="fas fa-check-double"></i> Select All</button>
                    <button type="button" class="btn btn-ghost btn-sm" id="deselectAll"><i class="fas fa-times"></i> Deselect All</button>
                </div>
            </div>
            <div style="padding:0;">
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th width="50"><input type="checkbox" id="checkAll" style="width:auto;"></th>
                                <th>#</th>
                                <th>Course Code</th>
                                <th>Course Title</th>
                                <th>Credit Hours</th>
                                <th>Department</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $i = 1; foreach($displayCourses as $course): if(!isset($course['id']) || !isset($course['course_code'])) { continue; } $assigned = false; if($semester_id > 0) { $check_sql = "SELECT * FROM semester_courses WHERE semester_id = ? AND course_id = ?"; $check = executeQuery($check_sql, [$semester_id, $course['id']]); if ($check) { $assigned = $check->get_result()->num_rows > 0; } ?>
                            <tr>
                                <td><input type="checkbox" name="courses[]" value="<?= $course['id'] ?>" class="course-checkbox" style="width:auto;" <?= (in_array($course['id'], $preselected_course)) ? 'checked' : '' ?> <?= $assigned ? 'disabled' : '' ?>></td>
                                <td><?= $i++ ?></td>
                                <td><strong style="color:var(--accent);"><?= htmlspecialchars($course['course_code'] ?? 'N/A') ?></strong></td>
                                <td><?= htmlspecialchars($course['course_title'] ?? 'N/A') ?></td>
                                <td><?= $course['credit_hours'] ?? 0 ?></td>
                                <td><?php $deptName = 'N/A'; foreach($programs as $p) { if($p['id'] == ($course['department_id'] ?? 0)) { $deptName = $p['name']; break; } } echo htmlspecialchars($deptName); ?></td>
                                <td>
                                    <?php if($assigned): ?>
                                        <span class="status-badge Active">Already Assigned</span>
                                    <?php else: ?>
                                        <span class="status-badge Inactive" style="background:var(--accent-light);color:var(--accent);border-color:var(--info-border);">Available</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    <?php else: ?>
        <div class="empty-state">
            <i class="fas fa-book"></i>
            <h5>No Courses Found</h5>
            <p><?= ($program_id > 0) ? 'No courses found for this program.' : 'Please select a program to view courses.' ?></p>
        </div>
    <?php endif; ?>
</form>

<script>
$(document).ready(function() {
    $('#checkAll').on('change', function() { $('.course-checkbox:not(:disabled)').prop('checked', $(this).prop('checked')); });
    $('#selectAll').on('click', function() { $('.course-checkbox:not(:disabled)').prop('checked', true); $('#checkAll').prop('checked', true); });
    $('#deselectAll').on('click', function() { $('.course-checkbox:not(:disabled)').prop('checked', false); $('#checkAll').prop('checked', false); });
    $('#program_id').on('change', function() { var programId = $(this).val(); var semesterId = $('#semester_id').val(); if(programId) { window.location.href = 'assign.php?program_id=' + programId + '&semester_id=' + semesterId; } });
    $('#semester_id').on('change', function() { var semesterId = $(this).val(); var programId = $('#program_id').val(); if(programId && semesterId) { window.location.href = 'assign.php?program_id=' + programId + '&semester_id=' + semesterId; } });
    $('#assignForm').on('submit', function(e) { var checked = $('.course-checkbox:checked').length; if(checked === 0) { e.preventDefault(); alert('Please select at least one course to assign!'); return false; } var program = $('#program_id').val(); var semester = $('#semester_id').val(); if(!program || !semester) { e.preventDefault(); alert('Please select both Program and Semester!'); return false; } return confirm('Are you sure you want to assign ' + checked + ' course(s) to this semester?'); });
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
