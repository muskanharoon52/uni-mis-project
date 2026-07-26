<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
requireLogin();

// ============================================
// SARI PROCESSING PEHLE KAREIN
// ============================================

$conn = getConnection();
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$course = null;
$error = '';
$success = '';

if ($id > 0) {
    $query = "SELECT c.*, p.program_name 
              FROM courses c 
              LEFT JOIN programs p ON c.program_id = p.program_id 
              WHERE c.course_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $course = $result->fetch_assoc();
    $stmt->close();
    
    if (!$course) {
        header("Location: index.php?error=Course not found");
        exit;
    }
} else {
    header("Location: index.php?error=Invalid course ID");
    exit;
}

// Get semesters for dropdown
$semester_query = "SELECT semester_id, semester_name FROM semesters ORDER BY semester_name";
$semester_result = $conn->query($semester_query);
$semesters = $semester_result ? $semester_result->fetch_all(MYSQLI_ASSOC) : [];

// Process form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $semester_id = (int)$_POST['semester_id'];
    $instructor = trim($_POST['instructor']);
    $max_students = (int)$_POST['max_students'];
    $room = trim($_POST['room']);
    $schedule = trim($_POST['schedule']);

    // Validation
    if (empty($semester_id) || $semester_id == 0) {
        $error = "Please select a semester";
    } elseif (empty($instructor)) {
        $error = "Instructor name is required";
    } elseif ($max_students < 1 || $max_students > 200) {
        $error = "Max students must be between 1 and 200";
    } else {
        // Check if semester_courses table exists
        $table_check = "SHOW TABLES LIKE 'semester_courses'";
        $table_result = $conn->query($table_check);
        
        if ($table_result->num_rows == 0) {
            // Create the table
            $create_table = "CREATE TABLE semester_courses (
                semester_course_id INT AUTO_INCREMENT PRIMARY KEY,
                course_id INT NOT NULL,
                semester_id INT NOT NULL,
                instructor VARCHAR(255),
                max_students INT DEFAULT 30,
                room VARCHAR(50),
                schedule VARCHAR(100),
                status ENUM('Active', 'Inactive') DEFAULT 'Active',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (course_id) REFERENCES courses(course_id) ON DELETE CASCADE,
                FOREIGN KEY (semester_id) REFERENCES semesters(semester_id) ON DELETE CASCADE,
                UNIQUE KEY unique_semester_course (course_id, semester_id)
            )";
            
            if (!$conn->query($create_table)) {
                $error = "Error creating semester_courses table: " . $conn->error;
            }
        }
        
        if (empty($error)) {
            // Check if already assigned
            $check_query = "SELECT semester_course_id FROM semester_courses WHERE course_id = ? AND semester_id = ?";
            $check_stmt = $conn->prepare($check_query);
            $check_stmt->bind_param("ii", $id, $semester_id);
            $check_stmt->execute();
            $check_result = $check_stmt->get_result();
            
            if ($check_result->num_rows > 0) {
                $error = "This course is already assigned to this semester!";
            } else {
                $insert_query = "INSERT INTO semester_courses (course_id, semester_id, instructor, max_students, room, schedule) 
                                VALUES (?, ?, ?, ?, ?, ?)";
                $stmt = $conn->prepare($insert_query);
                $stmt->bind_param("iisiss", $id, $semester_id, $instructor, $max_students, $room, $schedule);
                
                if ($stmt->execute()) {
                    $success = "Course has been added to semester successfully!";
                    $stmt->close();
                } else {
                    $error = "Error adding course to semester: " . $stmt->error;
                }
                $stmt->close();
            }
            $check_stmt->close();
        }
    }
}

// ============================================
// AB HEADER.PHP INCLUDE KAREIN
// ============================================
require_once __DIR__ . '/../includes/header.php';

$page_title = 'Add Course to Semester';

// ============================================
// INCLUDE SIDEBAR
// ============================================
include __DIR__ . '/../includes/sidebar.php';
?>

<style>
    .form-section {
        background: white;
        padding: 25px;
        border-radius: 10px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        margin-bottom: 20px;
    }
    
    .form-section-title {
        font-weight: 600;
        color: #2c3e50;
        margin-bottom: 20px;
        padding-bottom: 10px;
        border-bottom: 2px solid #f0f2f5;
    }
    
    .required-field::after {
        content: '*';
        color: #e74c3c;
        margin-left: 4px;
    }
    
    .course-info-box {
        background: #e8f4fd;
        padding: 15px;
        border-radius: 8px;
        border-left: 4px solid #3498db;
        margin-bottom: 20px;
    }
    
    .course-info-box .label {
        font-weight: 600;
        color: #2c3e50;
    }
    
    .course-info-box .value {
        color: #34495e;
    }
    
    /* FIX: Content container with margin-left to push content right */
    .courses-content {
        margin-left: 250px;
        padding: 20px;
        min-height: 100vh;
        background: #f5f6fa;
    }
    
    @media (max-width: 768px) {
        .courses-content {
            margin-left: 0;
            padding: 15px;
        }
    }
</style>

<!-- ============================================ -->
<!-- CONTENT WITH MARGIN-LEFT TO PUSH RIGHT -->
<!-- ============================================ -->
<div class="courses-content">
    <div class="container-fluid" style="padding: 0 !important;">
        
        <!-- Page Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4><i class="fas fa-calendar-plus"></i> Add Course to Semester</h4>
            <a href="index.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to List
            </a>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success); ?>
            </div>
        <?php endif; ?>

        <!-- Course Information -->
        <div class="course-info-box">
            <h6><i class="fas fa-info-circle"></i> Course Details</h6>
            <div class="row mt-2">
                <div class="col-md-3">
                    <span class="label">Course Code:</span>
                    <span class="value"><strong><?php echo htmlspecialchars($course['course_code']); ?></strong></span>
                </div>
                <div class="col-md-4">
                    <span class="label">Course Title:</span>
                    <span class="value"><?php echo htmlspecialchars($course['course_name']); ?></span>
                </div>
                <div class="col-md-2">
                    <span class="label">Credit Hours:</span>
                    <span class="value"><?php echo $course['credit_hours']; ?></span>
                </div>
                <div class="col-md-3">
                    <span class="label">Program:</span>
                    <span class="value"><?php echo htmlspecialchars($course['program_name'] ?? 'N/A'); ?></span>
                </div>
            </div>
        </div>

        <!-- Add to Semester Form -->
        <div class="form-section">
            <h6 class="form-section-title">
                <i class="fas fa-calendar-alt text-success"></i> Semester Assignment
            </h6>
            <form method="POST">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="required-field">Select Semester</label>
                        <select name="semester_id" class="form-select" required>
                            <option value="0">Select Semester</option>
                            <?php foreach ($semesters as $sem): ?>
                                <option value="<?php echo $sem['semester_id']; ?>">
                                    <?php echo htmlspecialchars($sem['semester_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label class="required-field">Instructor</label>
                        <input type="text" name="instructor" class="form-control" 
                               placeholder="e.g., Dr. John Smith" required>
                    </div>
                    
                    <div class="col-md-4 mb-3">
                        <label class="required-field">Max Students</label>
                        <input type="number" name="max_students" class="form-control" 
                               value="30" min="1" max="200" required>
                    </div>
                    
                    <div class="col-md-4 mb-3">
                        <label>Room Number</label>
                        <input type="text" name="room" class="form-control" 
                               placeholder="e.g., Room 201">
                    </div>
                    
                    <div class="col-md-4 mb-3">
                        <label>Schedule</label>
                        <input type="text" name="schedule" class="form-control" 
                               placeholder="e.g., Mon/Wed 10:00-11:30">
                    </div>
                </div>
                
                <div class="d-flex justify-content-end gap-2 mt-3">
                    <a href="index.php" class="btn btn-secondary">
                        <i class="fas fa-times"></i> Cancel
                    </a>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-plus-circle"></i> Add to Semester
                    </button>
                </div>
            </form>
        </div>
        
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>