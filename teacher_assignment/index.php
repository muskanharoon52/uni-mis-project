<?php
// teacher_assignment/index.php - Teacher Assignment Management

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../config/db_connect.php';
require_once __DIR__ . '/../modules/sso/includes/auth.php';

if (!isLoggedIn()) {
    header('Location: ' . BASE_URL . 'modules/sso/login.php');
    exit;
}

$user = getCurrentUser();
$role = $user['role_name'] ?? 'User';

$conn = getConnection();

// Get filter parameters
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// ============================================
// GET ALL TEACHERS
// ============================================
$teachers_query = "SELECT t.*, d.department_name 
                   FROM teachers t
                   LEFT JOIN departments d ON t.department_id = d.department_id
                   ORDER BY t.teacher_name";
$teachers_result = mysqli_query($conn, $teachers_query);
$all_teachers = [];
if ($teachers_result) {
    while ($row = mysqli_fetch_assoc($teachers_result)) {
        $all_teachers[] = $row;
    }
}

// ============================================
// HEADER INCLUDE
// ============================================
require_once __DIR__ . '/../includes/header.php';
$page_title = 'Teacher Management';
include __DIR__ . '/../includes/sidebar.php';
?>

<style>
    .teacher-content {
        margin-left: 250px;
        padding: 20px;
        min-height: 100vh;
        background: #f5f6fa;
    }
    
    .teacher-card {
        background: white;
        border-radius: 10px;
        padding: 20px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        transition: transform 0.3s, box-shadow 0.3s;
        margin-bottom: 20px;
        border-left: 4px solid #3498db;
    }
    
    .teacher-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 5px 15px rgba(0,0,0,0.15);
    }
    
    .teacher-card .teacher-name {
        font-weight: 600;
        color: #2c3e50;
        font-size: 18px;
    }
    
    .teacher-card .teacher-code {
        color: #7f8c8d;
        font-size: 13px;
    }
    
    .teacher-card .teacher-dept {
        color: #3498db;
        font-size: 13px;
    }
    
    .teacher-card .teacher-info {
        color: #555;
        font-size: 13px;
        margin-top: 5px;
    }
    
    .teacher-card .teacher-actions {
        margin-top: 12px;
        padding-top: 12px;
        border-top: 1px solid #e9ecef;
    }
    
    .teacher-card .teacher-actions .btn {
        margin-right: 5px;
    }
    
    .status-badge {
        padding: 4px 12px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 500;
    }
    
    .status-badge.Active {
        background: #d4edda;
        color: #155724;
    }
    
    .status-badge.Inactive {
        background: #f8d7da;
        color: #721c24;
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
    
    .filter-section {
        background: white;
        padding: 20px;
        border-radius: 10px;
        margin-bottom: 20px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }
    
    @media (max-width: 768px) {
        .teacher-content {
            margin-left: 0;
            padding: 15px;
        }
    }
</style>

<div class="teacher-content">
    <div class="container-fluid">
        
        <!-- Page Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4><i class="fas fa-chalkboard-teacher"></i> Teacher Management</h4>
            <div>
                <a href="add_teacher.php" class="btn btn-success">
                    <i class="fas fa-user-plus"></i> Add Teacher
                </a>
                <a href="assign.php" class="btn btn-primary">
                    <i class="fas fa-plus-circle"></i> Assign Course
                </a>
            </div>
        </div>

        <!-- Messages -->
        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <i class="fas fa-check-circle"></i> <?= htmlspecialchars($_GET['success']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($_GET['error']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Filter Section -->
        <div class="filter-section">
            <form method="GET" class="row g-3">
                <div class="col-md-4">
                    <input type="text" name="search" class="form-control" 
                           placeholder="Search teachers..." 
                           value="<?= htmlspecialchars($search) ?>">
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-search"></i> Search
                    </button>
                    <a href="index.php" class="btn btn-secondary">
                        <i class="fas fa-times"></i> Reset
                    </a>
                </div>
            </form>
        </div>

        <!-- Teachers Grid -->
        <div class="row">
            <?php if (!empty($all_teachers)): ?>
                <?php foreach ($all_teachers as $teacher): ?>
                    <div class="col-md-6 col-lg-4">
                        <div class="teacher-card">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <div class="teacher-name">
                                        <?= htmlspecialchars($teacher['teacher_name'] ?? 'N/A') ?>
                                    </div>
                                    <div class="teacher-code">
                                        <i class="fas fa-id-badge"></i> <?= htmlspecialchars($teacher['teacher_code'] ?? 'N/A') ?>
                                    </div>
                                    <div class="teacher-dept">
                                        <i class="fas fa-building"></i> <?= htmlspecialchars($teacher['department_name'] ?? 'N/A') ?>
                                    </div>
                                    <div class="teacher-info">
                                        <i class="fas fa-envelope"></i> <?= htmlspecialchars($teacher['email'] ?? 'N/A') ?>
                                    </div>
                                    <div class="teacher-info">
                                        <i class="fas fa-phone"></i> <?= htmlspecialchars($teacher['phone'] ?? 'N/A') ?>
                                    </div>
                                    <?php if (!empty($teacher['specialization'])): ?>
                                        <div class="teacher-info">
                                            <i class="fas fa-graduation-cap"></i> <?= htmlspecialchars($teacher['specialization']) ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <span class="status-badge <?= $teacher['status'] ?? 'Active' ?>">
                                    <?= $teacher['status'] ?? 'Active' ?>
                                </span>
                            </div>
                            <div class="teacher-actions">
                                <a href="edit_teacher.php?id=<?= $teacher['teacher_id'] ?>" class="btn btn-warning btn-sm">
                                    <i class="fas fa-edit"></i> Edit
                                </a>
                                <a href="assign.php?teacher_id=<?= $teacher['teacher_id'] ?>" class="btn btn-primary btn-sm">
                                    <i class="fas fa-plus"></i> Assign
                                </a>
                                <a href="delete.php?id=<?= $teacher['teacher_id'] ?>" 
                                   class="btn btn-danger btn-sm" 
                                   onclick="return confirm('Are you sure you want to delete this teacher?')">
                                    <i class="fas fa-trash"></i> Delete
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="col-12">
                    <div class="empty-state">
                        <i class="fas fa-chalkboard-teacher"></i>
                        <h5>No Teachers Found</h5>
                        <p class="text-muted">Add your first teacher to get started.</p>
                        <a href="add_teacher.php" class="btn btn-success">
                            <i class="fas fa-user-plus"></i> Add Teacher
                        </a>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>