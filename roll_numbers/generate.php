<?php
session_start();
$page_title = 'Generate Roll Numbers';

// Fix: Use correct path to includes folder
require_once '../includes/db.php';
require_once '../includes/functions.php';

// Handle single student generation
if(isset($_GET['student_id'])) {
    $student_id = (int)$_GET['student_id'];
    $result = generateRollNumber($student_id);
    
    if($result['success']) {
        $_SESSION['success'] = "Roll number {$result['roll_number']} generated successfully!";
    } else {
        $_SESSION['error'] = $result['message'];
    }
    header('Location: index.php');
    exit;
}

// Handle bulk generation
if(isset($_POST['generate_all'])) {
    $students = getPendingStudents();
    $generated = 0;
    $errors = [];
    
    foreach($students as $student) {
        $result = generateRollNumber($student['student_id']);
        if($result['success']) {
            $generated++;
        } else {
            $errors[] = "Student ID {$student['student_id']}: " . $result['message'];
        }
    }
    
    if($generated > 0) {
        $_SESSION['success'] = "Generated $generated roll numbers successfully!";
    }
    if(!empty($errors)) {
        $_SESSION['error'] = implode(" | ", $errors);
    }
    header('Location: index.php');
    exit;
}

// Get pending students
$pendingStudents = getPendingStudents();

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
                <i class="fas fa-sync-alt"></i> Generate Roll Numbers
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
            <h5><i class="fas fa-sync"></i> Generate Roll Numbers</h5>
        </div>
        <div class="card-body">
            <?php if(count($pendingStudents) > 0): ?>
                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i> 
                    <strong><?php echo count($pendingStudents); ?></strong> students need roll numbers.
                </div>
                
                <form method="POST" action="" class="mb-4">
                    <button type="submit" name="generate_all" class="btn btn-success btn-lg w-100">
                        <i class="fas fa-play"></i> Generate All (<?php echo count($pendingStudents); ?>)
                    </button>
                </form>
                
                <h5>Pending Students:</h5>
                <div class="table-responsive">
                    <table class="table table-hover datatable">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Student Name</th>
                                <th>Department</th>
                                <th>Session</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($pendingStudents as $student): ?>
                            <tr>
                                <td><?php echo $student['student_id']; ?></td>
                                <td><?php echo htmlspecialchars($student['full_name']); ?></td>
                                <td><span class="badge bg-info"><?php echo $student['department_code']; ?></span></td>
                                <td><?php echo htmlspecialchars($student['session_name']); ?></td>
                                <td>
                                    <a href="generate.php?student_id=<?php echo $student['student_id']; ?>" 
                                       class="btn btn-primary btn-sm">
                                        <i class="fas fa-sync"></i> Generate
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="text-center py-5">
                    <div class="empty-state">
                        <i class="fas fa-check-circle" style="color: #27ae60;"></i>
                        <h4 class="mt-3">All students have roll numbers!</h4>
                        <p class="text-muted">No pending roll number generation required.</p>
                        <a href="index.php" class="btn btn-primary mt-3">
                            <i class="fas fa-arrow-left"></i> Back to Dashboard
                        </a>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
function toggleSidebar() {
    document.querySelector('.sidebar-wrapper').classList.toggle('show');
}
</script>

<?php include '../includes/footer.php'; ?>