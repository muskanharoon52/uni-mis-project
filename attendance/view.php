<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
requireLogin();

$conn = getConnection();
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) {
    header("Location: index.php?error=Invalid ID");
    exit;
}

// Fetch attendance details
$sql = "SELECT 
            a.*,
            s.student_id,
            s.roll_no,
            u.full_name as student_name,
            c.course_code,
            c.course_name,
            c.credit_hours,
            f.faculty_id,
            u2.full_name as faculty_name
        FROM attendance a
        LEFT JOIN students s ON a.student_id = s.student_id
        LEFT JOIN users u ON s.user_id = u.user_id
        LEFT JOIN courses c ON a.course_id = c.course_id
        LEFT JOIN faculty f ON a.faculty_id = f.faculty_id
        LEFT JOIN users u2 ON f.user_id = u2.user_id
        WHERE a.attendance_id = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$attendance = $result->fetch_assoc();
$stmt->close();

if (!$attendance) {
    header("Location: index.php?error=Record not found");
    exit;
}

// ============================================
// HEADER INCLUDE
// ============================================
require_once __DIR__ . '/../includes/header.php';
$page_title = 'View Attendance';
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
    
    .status-badge {
        padding: 4px 12px;
        border-radius: 20px;
        font-size: 14px;
        font-weight: 500;
        text-transform: capitalize;
    }
    
    .status-badge.present {
        background: #d4edda;
        color: #155724;
    }
    
    .status-badge.absent {
        background: #f8d7da;
        color: #721c24;
    }
    
    .status-badge.late {
        background: #fff3cd;
        color: #856404;
    }
    
    .status-badge.excused {
        background: #cce5ff;
        color: #004085;
    }
    
    .header-icon {
        color: #667eea;
    }
    
    .btn-group-custom {
        display: flex;
        gap: 10px;
        flex-wrap: wrap;
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
            <h4><i class="fas fa-eye header-icon"></i> Attendance Details</h4>
            <div class="btn-group-custom">
                <a href="edit.php?id=<?= $id ?>" class="btn btn-warning">
                    <i class="fas fa-edit"></i> Edit
                </a>
                <a href="delete.php?id=<?= $id ?>" class="btn btn-danger" 
                   onclick="return confirm('Are you sure you want to delete this attendance record?')">
                    <i class="fas fa-trash"></i> Delete
                </a>
                <a href="index.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Back
                </a>
            </div>
        </div>

        <div class="view-container">
            <h5 class="mb-4 text-center text-primary">
                <i class="fas fa-clipboard-check me-2"></i>Attendance Record Details
            </h5>
            
            <div class="detail-row">
                <span class="detail-label"><i class="fas fa-user me-2"></i>Student</span>
                <span class="detail-value">
                    <strong><?= htmlspecialchars($attendance['student_name'] ?? 'N/A') ?></strong>
                    <br>
                    <small class="text-muted">ID: <?= htmlspecialchars($attendance['student_id']) ?></small>
                    <?php if ($attendance['roll_no']): ?>
                        <br>
                        <small class="text-muted">Roll No: <?= htmlspecialchars($attendance['roll_no']) ?></small>
                    <?php endif; ?>
                </span>
            </div>
            
            <div class="detail-row">
                <span class="detail-label"><i class="fas fa-book me-2"></i>Course</span>
                <span class="detail-value">
                    <strong><?= htmlspecialchars($attendance['course_name'] ?? 'N/A') ?></strong>
                    <br>
                    <small class="text-muted"><?= htmlspecialchars($attendance['course_code'] ?? 'N/A') ?></small>
                    <?php if ($attendance['credit_hours']): ?>
                        <span class="badge bg-info ms-2"><?= $attendance['credit_hours'] ?> Credits</span>
                    <?php endif; ?>
                </span>
            </div>
            
            <div class="detail-row">
                <span class="detail-label"><i class="fas fa-calendar-day me-2"></i>Date</span>
                <span class="detail-value">
                    <?= date('l, d F Y', strtotime($attendance['date'])) ?>
                </span>
            </div>
            
            <div class="detail-row">
                <span class="detail-label"><i class="fas fa-check-circle me-2"></i>Status</span>
                <span class="detail-value">
                    <span class="status-badge <?= $attendance['status'] ?>">
                        <i class="fas fa-<?= $attendance['status'] == 'present' ? 'check-circle' : ($attendance['status'] == 'absent' ? 'times-circle' : ($attendance['status'] == 'late' ? 'clock' : 'check-circle')) ?> me-1"></i>
                        <?= ucfirst($attendance['status']) ?>
                    </span>
                </span>
            </div>
            
            <div class="detail-row">
                <span class="detail-label"><i class="fas fa-comment me-2"></i>Remark</span>
                <span class="detail-value">
                    <?= htmlspecialchars($attendance['remark'] ?? 'No remarks added') ?>
                </span>
            </div>
            
            <div class="detail-row">
                <span class="detail-label"><i class="fas fa-user-tie me-2"></i>Marked By</span>
                <span class="detail-value">
                    <?= htmlspecialchars($attendance['faculty_name'] ?? 'N/A') ?>
                </span>
            </div>
            
            <div class="mt-4 text-center pt-3 border-top">
                <small class="text-muted">
                    <i class="fas fa-clock me-1"></i> 
                    Record ID: #<?= $attendance['attendance_id'] ?>
                </small>
            </div>
        </div>
        
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>