<?php
require_once 'includes/db.php';
require_once 'includes/auth.php';
requireSSO();

$user = getCurrentUser();
$full_name = $_SESSION['full_name'] ?? 'User';
$role_name = $_SESSION['role'] ?? 'sso';

// Get statistics
$stats = [];
$stats['students'] = getRow("SELECT COUNT(*) as count FROM students")['count'] ?? 0;
$stats['courses'] = getRow("SELECT COUNT(*) as count FROM courses")['count'] ?? 0;
$stats['applications'] = getRow("SELECT COUNT(*) as count FROM applications")['count'] ?? 0;
$stats['faculty'] = getRow("SELECT COUNT(*) as count FROM faculty")['count'] ?? 0;

include 'includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <!-- Sidebar -->
        <div class="col-md-2 p-0">
            <?php include 'includes/sidebar.php'; ?>
        </div>
        
        <!-- Main Content -->
        <div class="col-md-10 main-content">
            <!-- Welcome Section -->
            <div class="row mb-4">
                <div class="col-12">
                    <div class="card">
                        <div class="card-body">
                            <h2>Welcome back, <strong><?php echo $full_name; ?></strong>! 👋</h2>
                            <p class="text-muted">You are logged in as <strong><?php echo ucfirst($role_name); ?></strong></p>
                            <small><i class="far fa-calendar-alt"></i> <?php echo date('l, F j, Y'); ?></small>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Statistics -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="stat-card">
                        <div class="stat-icon text-primary">👨‍🎓</div>
                        <div class="stat-number"><?php echo $stats['students']; ?></div>
                        <div class="stat-label">Total Students</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card">
                        <div class="stat-icon text-success">📚</div>
                        <div class="stat-number"><?php echo $stats['courses']; ?></div>
                        <div class="stat-label">Total Courses</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card">
                        <div class="stat-icon text-warning">📝</div>
                        <div class="stat-number"><?php echo $stats['applications']; ?></div>
                        <div class="stat-label">Applications</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card">
                        <div class="stat-icon text-info">👨‍🏫</div>
                        <div class="stat-number"><?php echo $stats['faculty']; ?></div>
                        <div class="stat-label">Faculty</div>
                    </div>
                </div>
            </div>
            
            <!-- Quick Links -->
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h5><i class="fas fa-link"></i> Quick Actions</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-3 mb-3">
                                    <a href="<?php echo BASE_URL; ?>students/index.php" class="btn btn-primary btn-block w-100">
                                        <i class="fas fa-users"></i> View Students
                                    </a>
                                </div>
                                <div class="col-md-3 mb-3">
                                    <a href="<?php echo BASE_URL; ?>Courses/index.php" class="btn btn-success btn-block w-100">
                                        <i class="fas fa-book"></i> Manage Courses
                                    </a>
                                </div>
                                <div class="col-md-3 mb-3">
                                    <a href="<?php echo BASE_URL; ?>attendance/mark.php" class="btn btn-warning btn-block w-100">
                                        <i class="fas fa-clipboard-check"></i> Mark Attendance
                                    </a>
                                </div>
                                <div class="col-md-3 mb-3">
                                    <a href="<?php echo BASE_URL; ?>applications/index.php" class="btn btn-info btn-block w-100">
                                        <i class="fas fa-file-alt"></i> View Applications
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>