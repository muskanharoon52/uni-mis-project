<?php
// semester_courses/view.php - View course assignments

// Include database (session is already started in db.php)
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
requireLogin();

$conn = getConnection();

// Get course ID from URL
$course_id = isset($_GET['course_id']) ? (int)$_GET['course_id'] : 0;

if ($course_id == 0) {
    echo "<script>window.location.href='index.php?error=Invalid course ID';</script>";
    exit;
}

// Fetch course details
$course_query = "SELECT c.*, d.department_name, p.program_name 
                 FROM courses c
                 LEFT JOIN departments d ON c.department_id = d.department_id
                 LEFT JOIN programs p ON c.program_id = p.program_id
                 WHERE c.course_id = ?";
$course_stmt = $conn->prepare($course_query);

// Check if statement prepared successfully
if ($course_stmt === false) {
    die("Error preparing course query: " . $conn->error);
}

$course_stmt->bind_param("i", $course_id);
$course_stmt->execute();
$course_result = $course_stmt->get_result();
$course = $course_result->fetch_assoc();
$course_stmt->close();

if (!$course) {
    echo "<script>window.location.href='index.php?error=Course not found';</script>";
    exit;
}

// Fetch assigned semesters for this course
// Removed 's.year' since it doesn't exist
$assignments_query = "SELECT sc.*, s.semester_name 
                      FROM semester_courses sc
                      LEFT JOIN semesters s ON sc.semester_id = s.semester_id
                      WHERE sc.course_id = ?
                      ORDER BY s.semester_name";

$assignments_stmt = $conn->prepare($assignments_query);

// Check if statement prepared successfully
if ($assignments_stmt === false) {
    die("Error preparing assignments query: " . $conn->error);
}

$assignments_stmt->bind_param("i", $course_id);
$assignments_stmt->execute();
$assignments_result = $assignments_stmt->get_result();
$assignments = $assignments_result ? $assignments_result->fetch_all(MYSQLI_ASSOC) : [];
$assignments_stmt->close();

// ============================================
// HEADER INCLUDE KAREIN
// ============================================
require_once __DIR__ . '/../includes/header.php';

$page_title = 'View Course Assignments';
include __DIR__ . '/../includes/sidebar.php';
?>

<style>
    .semester-courses-content {
        margin-left: 250px;
        padding: 20px;
        min-height: 100vh;
        background: #f5f6fa;
    }
    
    .course-info-card {
        background: white;
        padding: 25px;
        border-radius: 10px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        margin-bottom: 20px;
    }
    
    .course-info-card .info-label {
        font-weight: 600;
        color: #7f8c8d;
        font-size: 13px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    
    .course-info-card .info-value {
        font-size: 16px;
        font-weight: 500;
        color: #2c3e50;
    }
    
    .course-code-badge {
        font-family: 'Courier New', monospace;
        font-weight: 700;
        color: #3498db;
        background: #e8f0fe;
        padding: 5px 15px;
        border-radius: 5px;
        font-size: 18px;
    }
    
    .empty-state {
        padding: 60px 0;
        text-align: center;
        color: #95a5a6;
    }
    
    .empty-state i {
        font-size: 64px;
        margin-bottom: 20px;
        opacity: 0.5;
    }
    
    .assignment-card {
        background: white;
        border-radius: 10px;
        padding: 15px 20px;
        margin-bottom: 10px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        border-left: 4px solid #3498db;
        transition: all 0.3s ease;
    }
    
    .assignment-card:hover {
        transform: translateX(5px);
        box-shadow: 0 3px 10px rgba(0,0,0,0.15);
    }
    
    .assignment-card .semester-name {
        font-weight: 600;
        color: #2c3e50;
        font-size: 16px;
    }
    
    .assignment-card .semester-year {
        color: #7f8c8d;
        font-size: 14px;
    }
    
    .assignment-card .badge-info {
        background: #e8f4fd;
        color: #3498db;
        padding: 3px 12px;
        border-radius: 15px;
        font-size: 12px;
    }
    
    .assignment-card .semester-details {
        display: flex;
        align-items: center;
        gap: 15px;
        flex-wrap: wrap;
    }
    
    @media (max-width: 768px) {
        .semester-courses-content {
            margin-left: 0;
            padding: 15px;
        }
    }
</style>

<div class="semester-courses-content">
    <div class="container-fluid" style="padding: 0 !important;">
        
        <!-- Page Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4><i class="fas fa-eye"></i> View Course Assignments</h4>
            <div>
                <a href="assign.php?course_id=<?= $course_id ?>" class="btn btn-primary">
                    <i class="fas fa-plus-circle"></i> Assign to Semester
                </a>
                <a href="index.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Back
                </a>
            </div>
        </div>

        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <i class="fas fa-check-circle"></i> 
                <?php echo htmlspecialchars($_GET['success']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <i class="fas fa-exclamation-circle"></i> 
                <?php echo htmlspecialchars($_GET['error']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Course Information -->
        <div class="course-info-card">
            <div class="row align-items-center">
                <div class="col-md-3">
                    <div class="course-code-badge">
                        <?= htmlspecialchars($course['course_code']) ?>
                    </div>
                </div>
                <div class="col-md-9">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="info-label">Course Title</div>
                            <div class="info-value"><?= htmlspecialchars($course['course_name']) ?></div>
                        </div>
                        <div class="col-md-3">
                            <div class="info-label">Credit Hours</div>
                            <div class="info-value">
                                <span class="badge bg-primary"><?= $course['credit_hours'] ?> Credits</span>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="info-label">Department</div>
                            <div class="info-value"><?= htmlspecialchars($course['department_name'] ?? 'N/A') ?></div>
                        </div>
                        <?php if (!empty($course['program_name'])): ?>
                        <div class="col-md-12 mt-2">
                            <div class="info-label">Program</div>
                            <div class="info-value"><?= htmlspecialchars($course['program_name']) ?></div>
                        </div>
                        <?php endif; ?>
                        <?php if (!empty($course['description'])): ?>
                        <div class="col-md-12 mt-2">
                            <div class="info-label">Description</div>
                            <div class="info-value"><?= htmlspecialchars($course['description']) ?></div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Assignments List -->
        <div class="card">
            <div class="card-header">
                <h5>
                    <i class="fas fa-calendar-alt"></i> 
                    Assigned Semesters (<?= count($assignments) ?>)
                </h5>
            </div>
            <div class="card-body">
                <?php if (!empty($assignments)): ?>
                    <?php foreach($assignments as $assignment): ?>
                        <?php 
                        // Check if we have the primary key column
                        $assignment_id = $assignment['id'] ?? $assignment['semester_course_id'] ?? $assignment['assignment_id'] ?? 0;
                        ?>
                        <div class="assignment-card">
                            <div class="row align-items-center">
                                <div class="col-md-6">
                                    <div class="semester-details">
                                        <div>
                                            <i class="fas fa-calendar-alt text-primary"></i>
                                            <span class="semester-name"><?= htmlspecialchars($assignment['semester_name'] ?? 'N/A') ?></span>
                                        </div>
                                        <?php if (!empty($assignment['year'])): ?>
                                        <div>
                                            <i class="fas fa-calendar"></i>
                                            <span class="semester-year">Year: <?= htmlspecialchars($assignment['year']) ?></span>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <span class="badge-info">
                                        <i class="fas fa-info-circle"></i> 
                                        Assigned: <?= date('d M Y', strtotime($assignment['created_at'] ?? date('Y-m-d'))) ?>
                                    </span>
                                </div>
                                <div class="col-md-3 text-end">
                                    <?php if ($assignment_id > 0): ?>
                                        <a href="remove.php?assignment_id=<?= $assignment_id ?>&course_id=<?= $course_id ?>" 
                                           class="btn btn-danger btn-sm"
                                           onclick="return confirm('Are you sure you want to remove this course from this semester?')">
                                            <i class="fas fa-trash"></i> Remove
                                        </a>
                                    <?php else: ?>
                                        <span class="text-muted">Cannot remove</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-calendar-alt"></i>
                        <h5>No Assignments</h5>
                        <p class="text-muted">This course is not assigned to any semester yet.</p>
                        <a href="assign.php?course_id=<?= $course_id ?>" class="btn btn-primary">
                            <i class="fas fa-plus-circle"></i> Assign to Semester
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>