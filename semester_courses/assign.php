<?php
// semester_courses/assign.php

// Include database (session is already started in db.php)
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
requireLogin();

$conn = getConnection();

// Get parameters
$course_id = isset($_GET['course_id']) ? intval($_GET['course_id']) : 0;
$program_id = isset($_GET['program_id']) ? intval($_GET['program_id']) : 0;
$semester_id = isset($_GET['semester_id']) ? intval($_GET['semester_id']) : 0;

// Fetch all programs
$programs_result = $conn->query("SELECT program_id as id, program_name as name FROM programs ORDER BY program_name");
$programs = $programs_result ? $programs_result->fetch_all(MYSQLI_ASSOC) : [];

// Fetch all semesters
$semesters_result = $conn->query("SELECT semester_id as id, semester_name FROM semesters ORDER BY semester_name");
$semesters = $semesters_result ? $semesters_result->fetch_all(MYSQLI_ASSOC) : [];

// Check what columns exist in semester_courses table
$columns_query = $conn->query("SHOW COLUMNS FROM semester_courses");
$columns = [];
if ($columns_query) {
    while($col = $columns_query->fetch_assoc()) {
        $columns[] = $col['Field'];
    }
}

// Check if specific columns exist
$has_program_column = in_array('program_id', $columns);
$has_assigned_date = in_array('assigned_date', $columns);
$has_assigned_by = in_array('assigned_by', $columns);
$has_created_at = in_array('created_at', $columns);

// If program is selected, fetch courses for that program
$programCourses = [];
if($program_id > 0) {
    $sql = "SELECT c.*, d.department_name, p.program_name 
            FROM courses c 
            LEFT JOIN departments d ON c.department_id = d.department_id
            LEFT JOIN programs p ON c.program_id = p.program_id
            WHERE c.program_id = $program_id 
            ORDER BY c.course_code";
    $programCourses_result = $conn->query($sql);
    
    if ($programCourses_result && $programCourses_result->num_rows > 0) {
        $programCourses = $programCourses_result->fetch_all(MYSQLI_ASSOC);
    }
}

// If no program selected, show ALL courses
if($program_id == 0) {
    $sql = "SELECT c.*, d.department_name, p.program_name 
            FROM courses c 
            LEFT JOIN departments d ON c.department_id = d.department_id
            LEFT JOIN programs p ON c.program_id = p.program_id
            ORDER BY c.course_code";
    $allCourses_result = $conn->query($sql);
    if ($allCourses_result) {
        $programCourses = $allCourses_result->fetch_all(MYSQLI_ASSOC);
    }
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
        $already_assigned = 0;
        
        foreach($selected_courses as $course_id) {
            // Build check query based on available columns
            $check_sql = "SELECT * FROM semester_courses WHERE semester_id = ? AND course_id = ?";
            $check_params = [$selected_semester, $course_id];
            $check_types = "ii";
            
            if($has_program_column) {
                $check_sql .= " AND program_id = ?";
                $check_params[] = $selected_program;
                $check_types .= "i";
            }
            
            $stmt = $conn->prepare($check_sql);
            if ($stmt === false) {
                $error = "Error preparing check query: " . $conn->error;
                break;
            }
            
            $stmt->bind_param($check_types, ...$check_params);
            $stmt->execute();
            $check_result = $stmt->get_result();
            
            if($check_result->num_rows == 0) {
                // Build insert query based on available columns
                $insert_fields = ["semester_id", "course_id"];
                $insert_values = [$selected_semester, $course_id];
                $insert_types = "ii";
                
                if($has_program_column) {
                    $insert_fields[] = "program_id";
                    $insert_values[] = $selected_program;
                    $insert_types .= "i";
                }
                
                if($has_assigned_date) {
                    $insert_fields[] = "assigned_date";
                    $insert_values[] = date('Y-m-d H:i:s');
                    $insert_types .= "s";
                }
                
                if($has_assigned_by) {
                    $insert_fields[] = "assigned_by";
                    $insert_values[] = $_SESSION['user_id'] ?? 1;
                    $insert_types .= "i";
                }
                
                if($has_created_at) {
                    $insert_fields[] = "created_at";
                    $insert_values[] = date('Y-m-d H:i:s');
                    $insert_types .= "s";
                }
                
                $insert_sql = "INSERT INTO semester_courses (" . implode(", ", $insert_fields) . ") 
                               VALUES (" . implode(", ", array_fill(0, count($insert_fields), "?")) . ")";
                
                $insert_stmt = $conn->prepare($insert_sql);
                if ($insert_stmt === false) {
                    $error = "Error preparing insert query: " . $conn->error;
                    break;
                }
                
                $insert_stmt->bind_param($insert_types, ...$insert_values);
                
                if($insert_stmt->execute()) {
                    $assigned_count++;
                } else {
                    $success = false;
                    $error = "Error assigning courses: " . $insert_stmt->error;
                    break;
                }
                $insert_stmt->close();
            } else {
                $already_assigned++;
            }
            $stmt->close();
        }
        
        if($success && $assigned_count > 0) {
            $success_msg = "$assigned_count course(s) assigned successfully!";
            if($already_assigned > 0) {
                $success_msg .= " ($already_assigned course(s) were already assigned)";
            }
        } elseif($success && $assigned_count == 0 && $already_assigned > 0) {
            $error = "All selected courses are already assigned to this semester!";
        }
    }
}

// If course_id is provided, pre-select it
$preselected_course = $course_id > 0 ? [$course_id] : [];

// ============================================
// HEADER INCLUDE KAREIN
// ============================================
require_once __DIR__ . '/../includes/header.php';

$page_title = 'Assign Courses to Semester';
include __DIR__ . '/../includes/sidebar.php';
?>

<style>
    .assign-content {
        margin-left: 250px;
        padding: 20px;
        min-height: 100vh;
        background: #f5f6fa;
    }
    
    .course-code-badge {
        font-family: 'Courier New', monospace;
        font-weight: 700;
        color: #3498db;
        background: #e8f0fe;
        padding: 3px 10px;
        border-radius: 5px;
        font-size: 14px;
    }
    
    .credit-badge {
        background: #f3e8ff;
        color: #7c3aed;
        padding: 2px 10px;
        border-radius: 15px;
        font-size: 12px;
        font-weight: 500;
    }
    
    .status-badge {
        padding: 4px 12px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 500;
    }
    
    .status-badge.assigned {
        background: #d4edda;
        color: #155724;
    }
    
    .status-badge.available {
        background: #cce5ff;
        color: #004085;
    }
    
    .selection-controls {
        display: flex;
        gap: 10px;
        align-items: center;
    }
    
    .selection-controls .btn {
        border-radius: 20px;
        padding: 5px 15px;
        font-size: 12px;
    }
    
    .form-section {
        background: white;
        padding: 20px;
        border-radius: 10px;
        margin-bottom: 20px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }
    
    .btn-action {
        border-radius: 20px;
        padding: 8px 25px;
        font-weight: 500;
    }
    
    .card-header {
        background: #f8f9fa;
        border-bottom: 1px solid #e9ecef;
    }
    
    .course-count-badge {
        background: #3498db;
        color: white;
        padding: 2px 10px;
        border-radius: 20px;
        font-size: 12px;
    }
    
    @media (max-width: 768px) {
        .assign-content {
            margin-left: 0;
            padding: 15px;
        }
    }
</style>

<div class="assign-content">
    <div class="container-fluid" style="padding: 0 !important;">
        
        <!-- Page Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4><i class="fas fa-tasks"></i> Assign Courses to Semester</h4>
            <div>
                <a href="index.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Back to Courses
                </a>
            </div>
        </div>

        <?php if($error): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <i class="fas fa-exclamation-circle"></i> 
                <?= htmlspecialchars($error) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <?php if($success_msg): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <i class="fas fa-check-circle"></i> 
                <?= htmlspecialchars($success_msg) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <form method="POST" action="" id="assignForm">
            <!-- Selection Form -->
            <div class="form-section">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label for="program_id" class="form-label">
                            <i class="fas fa-graduation-cap"></i> Select Program
                        </label>
                        <select name="program_id" id="program_id" class="form-select" required>
                            <option value="0">-- Select Program --</option>
                            <?php foreach($programs as $program): ?>
                                <option value="<?= $program['id'] ?>" 
                                        <?= ($program_id == $program['id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($program['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label for="semester_id" class="form-label">
                            <i class="fas fa-calendar-alt"></i> Select Semester
                        </label>
                        <select name="semester_id" id="semester_id" class="form-select" required>
                            <option value="0">-- Select Semester --</option>
                            <?php foreach($semesters as $semester): ?>
                                <option value="<?= $semester['id'] ?>"
                                        <?= ($semester_id == $semester['id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($semester['semester_name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-4 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary w-100 btn-action" id="assignBtn">
                            <i class="fas fa-save"></i> Assign Selected Courses
                        </button>
                    </div>
                </div>
            </div>

            <hr>

            <!-- Courses List with Checkboxes -->
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5>
                        <i class="fas fa-list"></i> Select Courses to Assign
                        <span class="course-count-badge">
                            <?= count($programCourses) ?> courses found
                        </span>
                    </h5>
                    <div class="selection-controls">
                        <button type="button" class="btn btn-info btn-sm" id="selectAll">
                            <i class="fas fa-check-double"></i> Select All
                        </button>
                        <button type="button" class="btn btn-secondary btn-sm" id="deselectAll">
                            <i class="fas fa-times"></i> Deselect All
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <?php if(!empty($programCourses)): ?>
                        <div class="table-responsive">
                            <table class="table table-hover" id="coursesTable">
                                <thead>
                                    <tr>
                                        <th width="50">
                                            <input type="checkbox" id="checkAll" class="form-check-input">
                                        </th>
                                        <th>#</th>
                                        <th>Course Code</th>
                                        <th>Course Title</th>
                                        <th>Credit Hours</th>
                                        <th>Department</th>
                                        <th>Program</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php $i = 1; ?>
                                    <?php foreach($programCourses as $course): ?>
                                        <?php 
                                        // Check if already assigned to this semester
                                        $assigned = false;
                                        if($semester_id > 0 && $program_id > 0) {
                                            $check_sql = "SELECT * FROM semester_courses 
                                                          WHERE semester_id = ? AND course_id = ?";
                                            $check_params = [$semester_id, $course['course_id']];
                                            $check_types = "ii";
                                            
                                            if($has_program_column) {
                                                $check_sql .= " AND program_id = ?";
                                                $check_params[] = $program_id;
                                                $check_types .= "i";
                                            }
                                            
                                            $stmt = $conn->prepare($check_sql);
                                            if ($stmt !== false) {
                                                $stmt->bind_param($check_types, ...$check_params);
                                                $stmt->execute();
                                                $check_result = $stmt->get_result();
                                                $assigned = $check_result->num_rows > 0;
                                                $stmt->close();
                                            }
                                        }
                                        ?>
                                        <tr>
                                            <td>
                                                <input type="checkbox" name="courses[]" 
                                                       value="<?= $course['course_id'] ?>" 
                                                       class="course-checkbox form-check-input"
                                                       <?= $assigned ? 'disabled' : '' ?>
                                                       <?= (in_array($course['course_id'], $preselected_course)) ? 'checked' : '' ?>>
                                            </td>
                                            <td><?= $i++ ?></td>
                                            <td>
                                                <span class="course-code-badge">
                                                    <?= htmlspecialchars($course['course_code'] ?? 'N/A') ?>
                                                </span>
                                            </td>
                                            <td><?= htmlspecialchars($course['course_name'] ?? 'N/A') ?></td>
                                            <td>
                                                <span class="credit-badge">
                                                    <?= $course['credit_hours'] ?? 0 ?> Credits
                                                </span>
                                            </td>
                                            <td><?= htmlspecialchars($course['department_name'] ?? 'N/A') ?></td>
                                            <td><?= htmlspecialchars($course['program_name'] ?? 'N/A') ?></td>
                                            <td>
                                                <?php if($assigned): ?>
                                                    <span class="status-badge assigned">✅ Already Assigned</span>
                                                <?php else: ?>
                                                    <span class="status-badge available">📝 Available</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-5">
                            <i class="fas fa-book fa-3x text-muted mb-3"></i>
                            <h5>No Courses Found</h5>
                            <p class="text-muted">
                                <?php if($program_id > 0): ?>
                                    No courses available for this program.
                                <?php else: ?>
                                    Please select a program from the dropdown above to see available courses.
                                <?php endif; ?>
                            </p>
                            <?php if($program_id > 0): ?>
                                <a href="../Courses/add.php" class="btn btn-primary">
                                    <i class="fas fa-plus-circle"></i> Add Course
                                </a>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </form>
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
        if(programId > 0) {
            window.location.href = 'assign.php?program_id=' + programId + '&semester_id=' + semesterId;
        } else {
            window.location.href = 'assign.php';
        }
    });

    // When semester changes, reload page with selected semester
    $('#semester_id').on('change', function() {
        var semesterId = $(this).val();
        var programId = $('#program_id').val();
        if(programId > 0 && semesterId > 0) {
            window.location.href = 'assign.php?program_id=' + programId + '&semester_id=' + semesterId;
        } else if(semesterId > 0) {
            window.location.href = 'assign.php?semester_id=' + semesterId;
        } else {
            window.location.href = 'assign.php';
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
        
        if(!program || program == 0 || !semester || semester == 0) {
            e.preventDefault();
            alert('⚠️ Please select both Program and Semester!');
            return false;
        }
        
        return confirm('✅ Are you sure you want to assign ' + checked + ' course(s) to this semester?');
    });
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>