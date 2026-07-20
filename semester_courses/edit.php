<?php
// semester_courses/assign.php

// Include database (session is already started in db.php)
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/header.php';

// Get parameters
$course_id = isset($_GET['course_id']) ? intval($_GET['course_id']) : 0;
$program_id = isset($_GET['program_id']) ? intval($_GET['program_id']) : 0;
$semester_id = isset($_GET['semester_id']) ? intval($_GET['semester_id']) : 0;

// Fetch all programs/departments
$programs = getRows("SELECT * FROM departments ORDER BY name");

// Fetch all semesters
$semesters = getRows("SELECT * FROM semesters ORDER BY semester_code");

// Fetch all courses - CHECK COLUMN NAMES
$courses = getRows("SELECT * FROM courses ORDER BY course_code");

// Debug: Check if courses have data
if (empty($courses)) {
    echo '<div class="alert alert-warning">No courses found in database. Please add courses first.</div>';
}

// If program is selected, fetch courses for that program
$programCourses = [];
if($program_id > 0) {
    $sql = "SELECT c.* FROM courses c 
            WHERE c.department_id = $program_id 
            ORDER BY c.course_code";
    $programCourses = getRows($sql);
}

// Handle form submission
$error = '';
$success_msg = '';

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $selected_program = isset($_POST['program_id']) ? intval($_POST['program_id']) : 0;
    $selected_semester = isset($_POST['semester_id']) ? intval($_POST['semester_id']) : 0;
    $selected_courses = isset($_POST['courses']) ? $_POST['courses'] : [];
    
    // Validate
    if($selected_program == 0 || $selected_semester == 0 || empty($selected_courses)) {
        $error = "Please select program, semester, and at least one course!";
    } else {
        // Insert assignments
        $success = true;
        $assigned_count = 0;
        
        foreach($selected_courses as $course_id) {
            // Check if already assigned
            $check_sql = "SELECT * FROM semester_courses 
                          WHERE semester_id = ? AND course_id = ? AND program_id = ?";
            $check = executeQuery($check_sql, [$selected_semester, $course_id, $selected_program]);
            if ($check) {
                $result = $check->get_result();
                if($result->num_rows == 0) {
                    $insert_sql = "INSERT INTO semester_courses 
                                   (semester_id, course_id, program_id, assigned_by, assigned_date) 
                                   VALUES (?, ?, ?, ?, NOW())";
                    $insert = executeQuery($insert_sql, [
                        $selected_semester, 
                        $course_id, 
                        $selected_program, 
                        $_SESSION['user_id'] ?? 1
                    ]);
                    
                    if($insert) {
                        $assigned_count++;
                    } else {
                        $success = false;
                        $error = "Error assigning courses: " . $conn->error;
                        break;
                    }
                }
            }
        }
        
        if($success && $assigned_count > 0) {
            $success_msg = "$assigned_count course(s) assigned successfully!";
        } elseif($success && $assigned_count == 0) {
            $error = "All selected courses are already assigned to this semester!";
        }
    }
}

// If course_id is provided, pre-select it
$preselected_course = $course_id > 0 ? [$course_id] : [];
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-tasks"></i> Assign Courses to Semester
                    </h3>
                    <div class="card-tools">
                        <a href="index.php" class="btn btn-secondary btn-sm">
                            <i class="fas fa-arrow-left"></i> Back to Courses
                        </a>
                    </div>
                </div>
                
                <div class="card-body">
                    <?php if($error): ?>
                        <div class="alert alert-danger alert-dismissible fade show">
                            <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?>
                            <button type="button" class="close" data-dismiss="alert">&times;</button>
                        </div>
                    <?php endif; ?>
                    
                    <?php if($success_msg): ?>
                        <div class="alert alert-success alert-dismissible fade show">
                            <i class="fas fa-check-circle"></i> <?= htmlspecialchars($success_msg) ?>
                            <button type="button" class="close" data-dismiss="alert">&times;</button>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="" id="assignForm">
                        <div class="row">
                            <!-- Program Selection -->
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="program_id">
                                        <i class="fas fa-university"></i> Select Program
                                    </label>
                                    <select name="program_id" id="program_id" class="form-control" required>
                                        <option value="">-- Select Program --</option>
                                        <?php foreach($programs as $program): ?>
                                            <option value="<?= $program['id'] ?>" 
                                                    <?= ($program_id == $program['id']) ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($program['name']) ?> (<?= $program['code'] ?>)
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>

                            <!-- Semester Selection -->
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="semester_id">
                                        <i class="fas fa-calendar-alt"></i> Select Semester
                                    </label>
                                    <select name="semester_id" id="semester_id" class="form-control" required>
                                        <option value="">-- Select Semester --</option>
                                        <?php foreach($semesters as $semester): ?>
                                            <option value="<?= $semester['id'] ?>"
                                                    <?= ($semester_id == $semester['id']) ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($semester['semester_name']) ?> 
                                                (<?= $semester['semester_code'] ?>)
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>&nbsp;</label>
                                    <button type="submit" class="btn btn-primary btn-block" id="assignBtn">
                                        <i class="fas fa-save"></i> Assign Selected Courses
                                    </button>
                                </div>
                            </div>
                        </div>

                        <hr>

                        <!-- Courses List with Checkboxes -->
                        <div class="row">
                            <div class="col-12">
                                <div class="card card-info">
                                    <div class="card-header">
                                        <h4 class="card-title">
                                            <i class="fas fa-list"></i> Select Courses to Assign
                                        </h4>
                                        <div class="card-tools">
                                            <button type="button" class="btn btn-sm btn-light" id="selectAll">
                                                <i class="fas fa-check-double"></i> Select All
                                            </button>
                                            <button type="button" class="btn btn-sm btn-light" id="deselectAll">
                                                <i class="fas fa-times"></i> Deselect All
                                            </button>
                                        </div>
                                    </div>
                                    <div class="card-body">
                                        <div class="table-responsive">
                                            <table class="table table-bordered table-hover" id="coursesTable">
                                                <thead class="thead-light">
                                                    <tr>
                                                        <th width="50">
                                                            <input type="checkbox" id="checkAll">
                                                        </th>
                                                        <th>#</th>
                                                        <th>Course Code</th>
                                                        <th>Course Title</th>
                                                        <th>Credit Hours</th>
                                                        <th>Department</th>
                                                        <th>Status</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php 
                                                    // Use the courses to display
                                                    $displayCourses = ($program_id > 0) ? $programCourses : $courses;
                                                    
                                                    if(!empty($displayCourses)):
                                                        $i = 1; 
                                                        foreach($displayCourses as $course):
                                                            // Check if course has required keys
                                                            if(!isset($course['id']) || !isset($course['course_code'])) {
                                                                continue; // Skip invalid courses
                                                            }
                                                            
                                                            // Check if already assigned to this semester
                                                            $assigned = false;
                                                            if($semester_id > 0) {
                                                                $check_sql = "SELECT * FROM semester_courses 
                                                                              WHERE semester_id = ? AND course_id = ?";
                                                                $check = executeQuery($check_sql, [$semester_id, $course['id']]);
                                                                if ($check) {
                                                                    $assigned = $check->get_result()->num_rows > 0;
                                                                }
                                                            }
                                                    ?>
                                                    <tr>
                                                        <td>
                                                            <input type="checkbox" name="courses[]" 
                                                                   value="<?= $course['id'] ?>" 
                                                                   class="course-checkbox"
                                                                   <?= (in_array($course['id'], $preselected_course)) ? 'checked' : '' ?>
                                                                   <?= $assigned ? 'disabled' : '' ?>>
                                                            <?php if($assigned): ?>
                                                                <span class="badge badge-success ml-1">Assigned</span>
                                                            <?php endif; ?>
                                                        </td>
                                                        <td><?= $i++ ?></td>
                                                        <td><strong><?= htmlspecialchars($course['course_code'] ?? 'N/A') ?></strong></td>
                                                        <td><?= htmlspecialchars($course['course_title'] ?? 'N/A') ?></td>
                                                        <td class="text-center"><?= $course['credit_hours'] ?? 0 ?></td>
                                                        <td>
                                                            <?php 
                                                            $deptName = 'N/A';
                                                            foreach($programs as $p) {
                                                                if($p['id'] == ($course['department_id'] ?? 0)) {
                                                                    $deptName = $p['name'];
                                                                    break;
                                                                }
                                                            }
                                                            echo htmlspecialchars($deptName);
                                                            ?>
                                                        </td>
                                                        <td>
                                                            <?php if($assigned): ?>
                                                                <span class="badge badge-success">✅ Already Assigned</span>
                                                            <?php else: ?>
                                                                <span class="badge badge-info">📝 Available</span>
                                                            <?php endif; ?>
                                                        </td>
                                                    </tr>
                                                    <?php endforeach; ?>
                                                    <?php else: ?>
                                                    <tr>
                                                        <td colspan="7" class="text-center text-muted">
                                                            <i class="fas fa-info-circle"></i> 
                                                            <?= ($program_id > 0) ? 'No courses found for this program.' : 'Please select a program to view courses.' ?>
                                                        </td>
                                                    </tr>
                                                    <?php endif; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // Select All / Deselect All functionality
    $('#checkAll').on('change', function() {
        $('.course-checkbox:not(:disabled)').prop('checked', $(this).prop('checked'));
    });

    $('#selectAll').on('click', function() {
        $('.course-checkbox:not(:disabled)').prop('checked', true);
        $('#checkAll').prop('checked', true);
    });

    $('#deselectAll').on('click', function() {
        $('.course-checkbox:not(:disabled)').prop('checked', false);
        $('#checkAll').prop('checked', false);
    });

    // When program changes, reload page with selected program
    $('#program_id').on('change', function() {
        var programId = $(this).val();
        var semesterId = $('#semester_id').val();
        if(programId) {
            window.location.href = 'assign.php?program_id=' + programId + '&semester_id=' + semesterId;
        }
    });

    // When semester changes, reload page with selected semester
    $('#semester_id').on('change', function() {
        var semesterId = $(this).val();
        var programId = $('#program_id').val();
        if(programId && semesterId) {
            window.location.href = 'assign.php?program_id=' + programId + '&semester_id=' + semesterId;
        }
    });

    // Form validation before submit
    $('#assignForm').on('submit', function(e) {
        var checked = $('.course-checkbox:checked').length;
        if(checked === 0) {
            e.preventDefault();
            alert('⚠️ Please select at least one course to assign!');
            return false;
        }
        
        var program = $('#program_id').val();
        var semester = $('#semester_id').val();
        
        if(!program || !semester) {
            e.preventDefault();
            alert('⚠️ Please select both Program and Semester!');
            return false;
        }
        
        return confirm('✅ Are you sure you want to assign ' + checked + ' course(s) to this semester?');
    });
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>