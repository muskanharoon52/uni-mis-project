<?php
session_start();
$page_title = 'Print Roll Numbers';

// Fix: Use correct path to includes folder
require_once '../includes/db.php';
require_once '../includes/functions.php';

// Get all students with roll numbers
$students = getStudentsWithRollNumbers();

include '../includes/header.php';
?>

<!-- Page Content -->
<div class="content-wrapper">
    <!-- Top Navbar -->
    <div class="top-navbar">
        <div>
            <button class="navbar-toggle-btn" onclick="toggleSidebar()">
                <i class="fas fa-bars"></i>
            </button>
            <h4 class="page-title d-inline-block ms-2">
                <i class="fas fa-print"></i> Roll Numbers List
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

    <div class="card">
        <div class="card-header">
            <div class="d-flex justify-content-between align-items-center">
                <h5><i class="fas fa-list"></i> Student Roll Numbers</h5>
                <div>
                    <button onclick="window.print()" class="btn btn-success btn-sm">
                        <i class="fas fa-print"></i> Print / PDF
                    </button>
                    <a href="index.php" class="btn btn-secondary btn-sm">
                        <i class="fas fa-arrow-left"></i> Back
                    </a>
                </div>
            </div>
        </div>
        <div class="card-body">
            <div class="text-center mb-4">
                <h4>Student Roll Numbers</h4>
                <p class="text-muted">Generated on: <?php echo date('F d, Y h:i A'); ?></p>
                <p class="text-muted">Total Students: <?php echo count($students); ?></p>
            </div>
            
            <?php if(count($students) > 0): ?>
                <div class="table-responsive">
                    <table class="table table-bordered table-hover datatable">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Roll Number</th>
                                <th>Student Name</th>
                                <th>Department</th>
                                <th>Session</th>
                                <th>Batch Year</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($students as $index => $student): ?>
                            <tr>
                                <td><?php echo $index + 1; ?></td>
                                <td><strong class="text-primary"><?php echo $student['roll_no']; ?></strong></td>
                                <td><?php echo htmlspecialchars($student['full_name']); ?></td>
                                <td>
                                    <span class="badge bg-info"><?php echo $student['department_code']; ?></span>
                                </td>
                                <td><?php echo $student['session_name']; ?></td>
                                <td><?php echo $student['batch_year']; ?></td>
                                <td>
                                    <span class="badge <?php echo $student['status'] == 'Active' ? 'bg-success' : 'bg-secondary'; ?>">
                                        <?php echo $student['status']; ?>
                                    </span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="text-center py-5">
                    <div class="empty-state">
                        <i class="fas fa-inbox"></i>
                        <h4 class="mt-3">No roll numbers generated yet</h4>
                        <p class="text-muted">Generate roll numbers first.</p>
                        <a href="generate.php" class="btn btn-primary mt-3">
                            <i class="fas fa-sync"></i> Generate Now
                        </a>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Print Styles -->
<style>
@media print {
    .top-navbar, .card-header .btn, .btn, .navbar-toggle-btn, 
    .sidebar-wrapper, .footer, .datatable_filter, .datatable_info, 
    .datatable_paginate {
        display: none !important;
    }
    .content-wrapper {
        margin-left: 0 !important;
        padding: 10px !important;
    }
    .card {
        border: 1px solid #ddd !important;
        box-shadow: none !important;
    }
    .card-header {
        background: #333 !important;
        color: white !important;
    }
    .table thead {
        background: #333 !important;
        color: white !important;
    }
    .table thead th {
        background: #333 !important;
        color: white !important;
    }
    .badge {
        border: 1px solid #ddd !important;
    }
}
</style>

<script>
function toggleSidebar() {
    document.querySelector('.sidebar-wrapper').classList.toggle('show');
}
</script>

<?php include '../includes/footer.php'; ?>