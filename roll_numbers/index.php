<?php
session_start();
$page_title = 'Dashboard | Roll Number Generation';

// Fix: Use correct path to includes folder
require_once '../includes/db.php';
require_once '../includes/functions.php';

// Get statistics
$stats = getStudentStatistics();

// Get recent students with pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 10;
$offset = ($page - 1) * $per_page;

// Get students with pagination
$students = getRecentStudents($per_page, $offset);

// Get total students count - FIXED: Use direct query instead of undefined function
$conn = getConnection();
$count_query = "SELECT COUNT(*) as total FROM students";
$count_result = $conn->query($count_query);
$total_students = $count_result ? $count_result->fetch_assoc()['total'] : 0;

// Include sidebar
include '../includes/sidebar.php';

// Include header
include '../includes/header.php';
?>

<style>
    /* Dashboard Styles */
    .content-wrapper {
        margin-left: 250px;
        padding: 20px;
        background: #f0f2f5;
        min-height: 100vh;
        transition: margin-left 0.3s ease;
    }
    
    /* Top Navbar */
    .top-navbar {
        background: white;
        padding: 15px 25px;
        border-radius: 10px;
        margin-bottom: 25px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.08);
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    
    .top-navbar .page-title {
        font-size: 20px;
        font-weight: 600;
        color: #2c3e50;
        margin: 0;
    }
    
    .top-navbar .page-title i {
        color: #3498db;
        margin-right: 10px;
    }
    
    .top-navbar .user-info {
        display: flex;
        align-items: center;
        gap: 15px;
    }
    
    .top-navbar .user-details {
        text-align: right;
        line-height: 1.2;
    }
    
    .top-navbar .user-details strong {
        display: block;
        font-size: 14px;
        color: #2c3e50;
    }
    
    .top-navbar .user-details small {
        font-size: 12px;
        color: #7f8c8d;
    }
    
    .top-navbar .avatar {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        background: #3498db;
        color: white;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 600;
        font-size: 18px;
    }
    
    .navbar-toggle-btn {
        background: none;
        border: none;
        font-size: 20px;
        color: #2c3e50;
        display: none;
        cursor: pointer;
        padding: 5px 10px;
    }
    
    /* Statistics Cards */
    .stat-card {
        background: white;
        padding: 25px;
        border-radius: 12px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.08);
        border-left: 4px solid #3498db;
        transition: transform 0.2s ease;
        height: 100%;
    }
    
    .stat-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 4px 20px rgba(0,0,0,0.12);
    }
    
    .stat-card .stat-label {
        font-size: 13px;
        color: #7f8c8d;
        font-weight: 500;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-bottom: 8px;
    }
    
    .stat-card .stat-number {
        font-size: 32px;
        font-weight: 700;
        color: #2c3e50;
    }
    
    .stat-card .stat-icon {
        font-size: 40px;
        opacity: 0.2;
        color: #3498db;
    }
    
    .stat-card .stat-trend {
        font-size: 12px;
        padding: 2px 10px;
        border-radius: 20px;
        display: inline-block;
        margin-top: 5px;
    }
    
    .stat-trend.up {
        background: #d4edda;
        color: #155724;
    }
    
    .stat-trend.down {
        background: #f8d7da;
        color: #721c24;
    }
    
    /* Table Styles */
    .card {
        border: none;
        border-radius: 12px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.08);
        overflow: hidden;
    }
    
    .card-header {
        background: white;
        padding: 20px 25px;
        border-bottom: 1px solid #e9ecef;
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 10px;
    }
    
    .card-header h5 {
        margin: 0;
        font-weight: 600;
        color: #2c3e50;
    }
    
    .card-header h5 i {
        color: #3498db;
        margin-right: 8px;
    }
    
    .card-body {
        padding: 0;
    }
    
    .table {
        margin-bottom: 0;
    }
    
    .table thead th {
        background: #f8f9fa;
        color: #2c3e50;
        font-weight: 600;
        font-size: 13px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        padding: 15px 20px;
        border-bottom: 2px solid #e9ecef;
    }
    
    .table tbody td {
        padding: 15px 20px;
        vertical-align: middle;
        border-bottom: 1px solid #f0f0f0;
    }
    
    .table tbody tr:hover {
        background: #f8f9fa;
    }
    
    .table tbody tr:last-child td {
        border-bottom: none;
    }
    
    .badge {
        padding: 5px 12px;
        border-radius: 20px;
        font-weight: 500;
        font-size: 12px;
    }
    
    .badge-success {
        background: #d4edda;
        color: #155724;
    }
    
    .badge-warning {
        background: #fff3cd;
        color: #856404;
    }
    
    .badge-info {
        background: #d1ecf1;
        color: #0c5460;
    }
    
    .badge-secondary {
        background: #e2e3e5;
        color: #383d41;
    }
    
    /* Empty State */
    .empty-state {
        padding: 60px 20px;
        text-align: center;
        color: #95a5a6;
    }
    
    .empty-state i {
        font-size: 64px;
        margin-bottom: 15px;
        opacity: 0.3;
    }
    
    .empty-state p {
        font-size: 16px;
    }
    
    /* Table Footer */
    .table-footer {
        padding: 15px 20px;
        background: #f8f9fa;
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 10px;
        border-top: 1px solid #e9ecef;
    }
    
    .table-footer .pagination-info {
        color: #7f8c8d;
        font-size: 14px;
    }
    
    .pagination .page-link {
        border-radius: 6px;
        margin: 0 3px;
        color: #2c3e50;
        border: 1px solid #e9ecef;
        padding: 5px 12px;
    }
    
    .pagination .page-link:hover {
        background: #e8f0fe;
        color: #3498db;
        border-color: #3498db;
    }
    
    .pagination .page-item.active .page-link {
        background: #3498db;
        border-color: #3498db;
        color: white;
    }
    
    .pagination .page-item.disabled .page-link {
        color: #c0c0c0;
        pointer-events: none;
    }
    
    /* Responsive */
    @media (max-width: 768px) {
        .content-wrapper {
            margin-left: 0;
            padding: 15px;
        }
        
        .navbar-toggle-btn {
            display: block;
        }
        
        .top-navbar {
            flex-direction: column;
            align-items: flex-start;
            gap: 10px;
            padding: 15px;
        }
        
        .top-navbar .user-info {
            width: 100%;
            justify-content: flex-start;
        }
        
        .stat-card {
            margin-bottom: 15px;
        }
        
        .stat-card .stat-number {
            font-size: 24px;
        }
        
        .card-header {
            flex-direction: column;
            align-items: stretch;
        }
        
        .card-header .btn-group {
            display: flex;
            gap: 5px;
            flex-wrap: wrap;
        }
        
        .card-header .btn-group .btn {
            flex: 1;
            min-width: 80px;
        }
        
        .table-footer {
            flex-direction: column;
        }
        
        .table-responsive {
            font-size: 13px;
        }
        
        .table thead th,
        .table tbody td {
            padding: 10px 12px;
        }
    }
</style>

<!-- Page Content -->
<div class="content-wrapper">
    <!-- Top Navbar -->
    <div class="top-navbar">
        <div>
            <button class="navbar-toggle-btn" onclick="toggleSidebar()">
                <i class="fas fa-bars"></i>
            </button>
            <h4 class="page-title d-inline-block ms-2">
                <i class="fas fa-id-card"></i> Roll Number Dashboard
            </h4>
        </div>
        <div class="user-info">
            <div class="user-details">
                <strong>Admin</strong>
                <small>Admission Office</small>
            </div>
            <div class="avatar">
                <i class="fas fa-user"></i>
            </div>
        </div>
    </div>

    <!-- Flash Messages -->
    <?php echo displayFlashMessages(); ?>

    <!-- Statistics Cards -->
    <div class="row g-4 mb-4">
        <div class="col-md-3 col-sm-6">
            <div class="stat-card">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <div class="stat-label">Total Students</div>
                        <div class="stat-number"><?php echo number_format($stats['total'] ?? 0); ?></div>
                        <span class="stat-trend up">
                            <i class="fas fa-arrow-up"></i> 12%
                        </span>
                    </div>
                    <div class="stat-icon">
                        <i class="fas fa-users"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="stat-card" style="border-left-color: #27ae60;">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <div class="stat-label">With Roll Numbers</div>
                        <div class="stat-number text-success"><?php echo number_format($stats['with_roll_no'] ?? 0); ?></div>
                        <span class="stat-trend up">
                            <i class="fas fa-arrow-up"></i> 8%
                        </span>
                    </div>
                    <div class="stat-icon text-success">
                        <i class="fas fa-check-circle"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="stat-card" style="border-left-color: #f39c12;">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <div class="stat-label">Pending Generation</div>
                        <div class="stat-number text-warning"><?php echo number_format($stats['without_roll_no'] ?? 0); ?></div>
                        <span class="stat-trend down">
                            <i class="fas fa-arrow-down"></i> 5%
                        </span>
                    </div>
                    <div class="stat-icon text-warning">
                        <i class="fas fa-clock"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="stat-card" style="border-left-color: #3498db;">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <div class="stat-label">Active Students</div>
                        <div class="stat-number text-info"><?php echo number_format($stats['active'] ?? 0); ?></div>
                        <span class="stat-trend up">
                            <i class="fas fa-arrow-up"></i> 15%
                        </span>
                    </div>
                    <div class="stat-icon text-info">
                        <i class="fas fa-user-check"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Students Table -->
    <div class="card">
        <div class="card-header">
            <h5><i class="fas fa-list"></i> Recent Students</h5>
            <div class="btn-group">
                <a href="generate.php" class="btn btn-primary btn-sm">
                    <i class="fas fa-sync"></i> Generate Missing
                </a>
                <a href="print.php" class="btn btn-success btn-sm">
                    <i class="fas fa-print"></i> Print All
                </a>
                <a href="export.php" class="btn btn-info btn-sm">
                    <i class="fas fa-file-export"></i> Export
                </a>
            </div>
        </div>
        <div class="card-body">
            <?php if (!empty($students)): ?>
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Student Name</th>
                                <th>Department</th>
                                <th>Session</th>
                                <th>Roll Number</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($students as $index => $student): ?>
                            <tr>
                                <td><?php echo $offset + $index + 1; ?></td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="avatar me-2" style="width: 32px; height: 32px; font-size: 14px; background: <?php echo $student['roll_no'] ? '#27ae60' : '#f39c12'; ?>;">
                                            <?php echo strtoupper(substr($student['full_name'], 0, 1)); ?>
                                        </div>
                                        <div>
                                            <strong><?php echo htmlspecialchars($student['full_name']); ?></strong>
                                            <br>
                                            <small class="text-muted"><?php echo htmlspecialchars($student['email'] ?? ''); ?></small>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge badge-info">
                                        <?php echo $student['department_code'] ?? 'N/A'; ?>
                                    </span>
                                </td>
                                <td><?php echo $student['session_name'] ?? 'N/A'; ?></td>
                                <td>
                                    <?php if($student['roll_no']): ?>
                                        <span class="badge badge-success">
                                            <i class="fas fa-check"></i> <?php echo htmlspecialchars($student['roll_no']); ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="badge badge-warning">
                                            <i class="fas fa-clock"></i> Pending
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge <?php echo $student['status'] == 'Active' ? 'badge-success' : 'badge-secondary'; ?>">
                                        <?php echo $student['status']; ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if(!$student['roll_no']): ?>
                                        <a href="generate.php?student_id=<?php echo $student['student_id']; ?>" 
                                           class="btn btn-primary btn-sm">
                                            <i class="fas fa-sync"></i> Generate
                                        </a>
                                    <?php else: ?>
                                        <button class="btn btn-success btn-sm" disabled>
                                            <i class="fas fa-check"></i> Done
                                        </button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <!-- Table Footer with Pagination -->
                <div class="table-footer">
                    <div class="pagination-info">
                        Showing <?php echo $offset + 1; ?> to <?php echo min($offset + $per_page, $total_students); ?> of <?php echo number_format($total_students); ?> entries
                    </div>
                    <?php if ($total_students > $per_page): 
                        $total_pages = ceil($total_students / $per_page);
                    ?>
                        <nav>
                            <ul class="pagination pagination-sm mb-0">
                                <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                                    <a class="page-link" href="?page=<?php echo $page - 1; ?>">
                                        <i class="fas fa-chevron-left"></i>
                                    </a>
                                </li>
                                <?php 
                                $start_page = max(1, $page - 2);
                                $end_page = min($total_pages, $page + 2);
                                for ($i = $start_page; $i <= $end_page; $i++): 
                                ?>
                                    <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                        <a class="page-link" href="?page=<?php echo $i; ?>">
                                            <?php echo $i; ?>
                                        </a>
                                    </li>
                                <?php endfor; ?>
                                <?php if ($end_page < $total_pages): ?>
                                    <li class="page-item disabled">
                                        <span class="page-link">...</span>
                                    </li>
                                    <li class="page-item">
                                        <a class="page-link" href="?page=<?php echo $total_pages; ?>">
                                            <?php echo $total_pages; ?>
                                        </a>
                                    </li>
                                <?php endif; ?>
                                <li class="page-item <?php echo $page >= $total_pages ? 'disabled' : ''; ?>">
                                    <a class="page-link" href="?page=<?php echo $page + 1; ?>">
                                        <i class="fas fa-chevron-right"></i>
                                    </a>
                                </li>
                            </ul>
                        </nav>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-inbox"></i>
                    <p>No students found in the system</p>
                    <a href="../students/add.php" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Add First Student
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
function toggleSidebar() {
    document.querySelector('.sidebar').classList.toggle('show');
    document.querySelector('.sidebar-overlay').classList.toggle('show');
}

// Auto-hide flash messages after 5 seconds
document.addEventListener('DOMContentLoaded', function() {
    const flashMessages = document.querySelectorAll('.alert');
    flashMessages.forEach(function(msg) {
        setTimeout(function() {
            msg.style.transition = 'opacity 0.5s ease';
            msg.style.opacity = '0';
            setTimeout(function() {
                msg.remove();
            }, 500);
        }, 5000);
    });
});
</script>

<?php include '../includes/footer.php'; ?>