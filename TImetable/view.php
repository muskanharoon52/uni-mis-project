<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
requireLogin();

$conn = getConnection();
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) {
    header("Location: index.php?error=Invalid ID");
    exit;
}

// Fetch class details
$sql = "SELECT 
            t.*,
            c.course_code,
            c.course_name as course_title,
            c.credit_hours,
            tch.teacher_name,
            tch.specialization,
            tch.email as teacher_email,
            tch.phone as teacher_phone,
            s.semester_name,
            ses.session_name,
            d.department_name,
            p.program_name
        FROM timetable t
        LEFT JOIN courses c ON t.course_id = c.course_id
        LEFT JOIN teachers tch ON t.teacher_id = tch.teacher_id
        LEFT JOIN semesters s ON t.semester_id = s.semester_id
        LEFT JOIN sessions ses ON t.session_id = ses.session_id
        LEFT JOIN departments d ON c.department_id = d.department_id
        LEFT JOIN programs p ON c.program_id = p.program_id
        WHERE t.id = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$class = $result->fetch_assoc();
$stmt->close();

if (!$class) {
    header("Location: index.php?error=Record not found");
    exit;
}

// ============================================
// HEADER INCLUDE
// ============================================
require_once __DIR__ . '/../includes/header.php';
$page_title = 'View Class Details';
include __DIR__ . '/../includes/sidebar.php';
?>

<style>
    .view-content {
        margin-left: 250px;
        padding: 20px;
        min-height: 100vh;
        background: #f5f6fa;
    }
    
    .view-container {
        max-width: 700px;
        margin: 0 auto;
        background: white;
        padding: 30px;
        border-radius: 10px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }
    
    .detail-row {
        display: flex;
        padding: 12px 0;
        border-bottom: 1px solid #f0f2f5;
    }
    
    .detail-row:last-child {
        border-bottom: none;
    }
    
    .detail-label {
        font-weight: 600;
        color: #2c3e50;
        width: 150px;
        flex-shrink: 0;
    }
    
    .detail-value {
        color: #555;
        flex: 1;
    }
    
    .detail-value .badge {
        font-size: 0.9rem;
        padding: 5px 15px;
    }
    
    .day-badge {
        padding: 6px 20px;
        border-radius: 20px;
        font-weight: 600;
    }
    .day-monday { background: #e3f2fd; color: #0d47a1; }
    .day-tuesday { background: #f3e5f5; color: #4a148c; }
    .day-wednesday { background: #e8f5e9; color: #1b5e20; }
    .day-thursday { background: #fff3e0; color: #e65100; }
    .day-friday { background: #fce4ec; color: #880e4f; }
    .day-saturday { background: #e0f7fa; color: #006064; }
    .day-sunday { background: #f1f8e9; color: #33691e; }
    
    .header-icon {
        color: #667eea;
    }
    
    @media (max-width: 768px) {
        .view-content {
            margin-left: 0;
            padding: 15px;
        }
        
        .view-container {
            padding: 20px;
        }
        
        .detail-row {
            flex-direction: column;
            padding: 10px 0;
        }
        
        .detail-label {
            width: 100%;
            margin-bottom: 5px;
        }
    }
</style>

<div class="view-content">
    <div class="container-fluid">
        
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4><i class="fas fa-eye header-icon"></i> Class Details</h4>
            <div>
                <a href="edit.php?id=<?= $id ?>" class="btn btn-warning">
                    <i class="fas fa-edit"></i> Edit
                </a>
                <a href="index.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Back
                </a>
            </div>
        </div>

        <div class="view-container">
            <h5 class="mb-4 text-center text-primary">
                <i class="fas fa-calendar-alt me-2"></i>Class Schedule Details
            </h5>
            
            <div class="detail-row">
                <span class="detail-label"><i class="fas fa-calendar-day me-2"></i>Day</span>
                <span class="detail-value">
                    <span class="day-badge day-<?= strtolower($class['day_of_week']) ?>">
                        <?= htmlspecialchars($class['day_of_week']) ?>
                    </span>
                </span>
            </div>
            
            <div class="detail-row">
                <span class="detail-label"><i class="fas fa-clock me-2"></i>Time</span>
                <span class="detail-value">
                    <strong><?= date('g:i A', strtotime($class['start_time'])) ?></strong>
                    to
                    <strong><?= date('g:i A', strtotime($class['end_time'])) ?></strong>
                </span>
            </div>
            
            <div class="detail-row">
                <span class="detail-label"><i class="fas fa-book me-2"></i>Course</span>
                <span class="detail-value">
                    <strong><?= htmlspecialchars($class['course_title']) ?></strong>
                    <br>
                    <span class="text-muted"><?= htmlspecialchars($class['course_code']) ?></span>
                    <?php if($class['credit_hours']): ?>
                        <span class="badge bg-info ms-2"><?= $class['credit_hours'] ?> Credits</span>
                    <?php endif; ?>
                </span>
            </div>
            
            <div class="detail-row">
                <span class="detail-label"><i class="fas fa-user-tie me-2"></i>Teacher</span>
                <span class="detail-value">
                    <strong><?= htmlspecialchars($class['teacher_name']) ?></strong>
                    <?php if($class['specialization']): ?>
                        <br><span class="text-muted"><?= htmlspecialchars($class['specialization']) ?></span>
                    <?php endif; ?>
                    <?php if($class['teacher_email']): ?>
                        <br><small><i class="fas fa-envelope me-1"></i><?= htmlspecialchars($class['teacher_email']) ?></small>
                    <?php endif; ?>
                    <?php if($class['teacher_phone']): ?>
                        <br><small><i class="fas fa-phone me-1"></i><?= htmlspecialchars($class['teacher_phone']) ?></small>
                    <?php endif; ?>
                </span>
            </div>
            
            <div class="detail-row">
                <span class="detail-label"><i class="fas fa-door-open me-2"></i>Room</span>
                <span class="detail-value">
                    <strong><?= htmlspecialchars($class['room_no']) ?></strong>
                </span>
            </div>
            
            <div class="detail-row">
                <span class="detail-label"><i class="fas fa-users me-2"></i>Section</span>
                <span class="detail-value">
                    <span class="badge bg-warning text-dark"><?= htmlspecialchars($class['section']) ?></span>
                </span>
            </div>
            
            <div class="detail-row">
                <span class="detail-label"><i class="fas fa-graduation-cap me-2"></i>Semester</span>
                <span class="detail-value">
                    <?= htmlspecialchars($class['semester_name']) ?>
                </span>
            </div>
            
            <div class="detail-row">
                <span class="detail-label"><i class="fas fa-calendar-alt me-2"></i>Session</span>
                <span class="detail-value">
                    <?= htmlspecialchars($class['session_name']) ?>
                </span>
            </div>
            
            <div class="detail-row">
                <span class="detail-label"><i class="fas fa-building me-2"></i>Department</span>
                <span class="detail-value">
                    <?= htmlspecialchars($class['department_name'] ?? 'N/A') ?>
                </span>
            </div>
            
            <div class="detail-row">
                <span class="detail-label"><i class="fas fa-tag me-2"></i>Program</span>
                <span class="detail-value">
                    <?= htmlspecialchars($class['program_name'] ?? 'N/A') ?>
                </span>
            </div>
            
            <div class="mt-4 text-center">
                <a href="edit.php?id=<?= $id ?>" class="btn btn-warning">
                    <i class="fas fa-edit"></i> Edit Class
                </a>
                <a href="delete.php?id=<?= $id ?>" class="btn btn-danger" 
                   onclick="return confirm('Are you sure you want to delete this class?')">
                    <i class="fas fa-trash"></i> Delete Class
                </a>
            </div>
        </div>
        
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>