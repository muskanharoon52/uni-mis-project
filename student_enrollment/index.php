<?php
// student_enrollment/index.php - Section Management

require_once __DIR__ . '/../config/db_connect.php';
require_once __DIR__ . '/../modules/sso/includes/auth.php';

if (!isLoggedIn()) {
    header('Location: ' . BASE_URL . 'login.php');
    exit;
}

$user = getCurrentUser();
$role = strtolower($user['role_name'] ?? 'user');

if (!in_array($role, ['sso', 'admin'])) {
    header('Location: ' . BASE_URL . 'dashboard.php');
    exit;
}

$conn = getConnection();

// Get all sections with program and course info
$sections_query = "SELECT s.*, p.program_name, c.course_name, c.course_code
                   FROM sections s
                   LEFT JOIN programs p ON s.program_id = p.program_id
                   LEFT JOIN courses c ON s.course_id = c.course_id
                   ORDER BY p.program_name, s.section_name";
$sections_result = mysqli_query($conn, $sections_query);
$sections = [];
if ($sections_result) {
    while ($row = mysqli_fetch_assoc($sections_result)) {
        $sections[] = $row;
    }
}

// ============================================
// HEADER INCLUDE
// ============================================
include __DIR__ . '/../includes/header.php';
$page_title = 'Sections';
include __DIR__ . '/../includes/sidebar.php';

$success_msg = isset($_GET['success']) ? $_GET['success'] : '';
$error_msg = isset($_GET['error']) ? $_GET['error'] : '';
?>

<style>
.section-content { margin-left: 250px; padding: 20px; min-height: 100vh; background: #f5f6fa; }
@media (max-width: 768px) { .section-content { margin-left: 0; padding: 15px; } }
.stats-box { background: #f8f9fa; padding: 10px 20px; border-radius: 8px; display: inline-block; margin-right: 15px; }
</style>

<div class="section-content">
    <div class="container-fluid">
        
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4><i class="fas fa-layer-group"></i> Section Management</h4>
            <a href="add_section.php" class="btn btn-primary">
                <i class="fas fa-plus"></i> Add Section
            </a>
        </div>

        <?php if ($success_msg): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success_msg); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if ($error_msg): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error_msg); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Sections Table -->
        <div class="card">
            <div class="card-header">
                <h5><i class="fas fa-list"></i> All Sections (<?php echo count($sections); ?>)</h5>
            </div>
            <div class="card-body">
                <?php if (!empty($sections)): ?>
                    <div class="table-responsive">
                        <table class="table table-hover table-bordered">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Section Name</th>
                                    <th>Course</th>
                                    <th>Program</th>
                                    <th>Semester</th>
                                    <th>Capacity</th>
                                    <th>Enrolled</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $count = 1; ?>
                                <?php foreach($sections as $section): ?>
                                    <tr>
                                        <td><?php echo $count++; ?></td>
                                        <td>
                                            <strong><?php echo htmlspecialchars($section['section_name']); ?></strong>
                                        </td>
                                        <td>
                                            <?php 
                                            $course_display = '';
                                            if (!empty($section['course_code'])) {
                                                $course_display .= $section['course_code'] . ' - ';
                                            }
                                            $course_display .= $section['course_name'] ?? 'N/A';
                                            echo htmlspecialchars($course_display);
                                            ?>
                                        </td>
                                        <td><?php echo htmlspecialchars($section['program_name'] ?? 'N/A'); ?></td>
                                        <td><?php echo htmlspecialchars($section['semester_id'] ?? 'N/A'); ?></td>
                                        <td><?php echo $section['capacity'] ?? 'N/A'; ?></td>
                                        <td>
                                            <?php 
                                            $enrolled = $section['enrolled_count'] ?? 0;
                                            $capacity = $section['capacity'] ?? 30;
                                            $percentage = $capacity > 0 ? round(($enrolled / $capacity) * 100) : 0;
                                            $bar_class = $percentage >= 90 ? 'danger' : ($percentage >= 70 ? 'warning' : 'success');
                                            ?>
                                            <div class="d-flex align-items-center">
                                                <span class="me-2"><?php echo $enrolled; ?>/<?php echo $capacity; ?></span>
                                                <div class="progress" style="width: 80px; height: 8px;">
                                                    <div class="progress-bar bg-<?php echo $bar_class; ?>" 
                                                         role="progressbar" 
                                                         style="width: <?php echo $percentage; ?>%;"
                                                         aria-valuenow="<?php echo $percentage; ?>" 
                                                         aria-valuemin="0" 
                                                         aria-valuemax="100">
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <?php 
                                            $status = $section['status'] ?? 'Active';
                                            $badge_class = $status == 'Active' ? 'success' : 'secondary';
                                            ?>
                                            <span class="badge bg-<?php echo $badge_class; ?>">
                                                <?php echo htmlspecialchars($status); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="d-flex gap-1">
                                                <a href="student_list.php?section=<?php echo $section['section_id']; ?>" 
                                                   class="btn btn-sm btn-info" title="View Students">
                                                    <i class="fas fa-users"></i>
                                                </a>
                                                <a href="edit_section.php?id=<?php echo $section['section_id']; ?>" 
                                                   class="btn btn-sm btn-warning" title="Edit Section">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <a href="enroll_student.php?section=<?php echo $section['section_id']; ?>" 
                                                   class="btn btn-sm btn-success" title="Enroll Student">
                                                    <i class="fas fa-user-plus"></i>
                                                </a>
                                                <a href="delete_section.php?id=<?php echo $section['section_id']; ?>" 
                                                   class="btn btn-sm btn-danger" title="Delete Section"
                                                   onclick="return confirm('Are you sure you want to delete this section?')">
                                                    <i class="fas fa-trash"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="text-center py-4">
                        <i class="fas fa-layer-group fa-3x text-muted mb-3"></i>
                        <p>No sections found.</p>
                        <a href="add_section.php" class="btn btn-primary">
                            <i class="fas fa-plus"></i> Add First Section
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>