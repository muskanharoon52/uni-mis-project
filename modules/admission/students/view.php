<?php
require_once __DIR__ . '/../config/database.php';
$page_title = 'View Student';
include __DIR__ . '/../includes/header.php';

$id = $_GET['id'] ?? 0;

// =============================================
// HANDLE PASSWORD RESET
// =============================================
$new_password_display = ''; // Variable to hold the new password for display

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'reset_student_password') {
    $user_id = $_POST['user_id'] ?? 0;
    $new_password = $_POST['new_password'] ?? '';
    
    if ($user_id && !empty($new_password)) {
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        
        $reset_stmt = $pdo->prepare("UPDATE users SET password_hash = ? WHERE user_id = ?");
        if ($reset_stmt->execute([$hashed_password, $user_id])) {
            // Save the password to show it in the box immediately after redirect
            $new_password_display = $new_password;
            setFlash('success', '✅ Student password reset successfully! New Password: ' . htmlspecialchars($new_password));
            
            // Reload the page and PASS the new password clearly in the URL
            header('Location: view.php?id=' . $id . '&new_pw=' . urlencode($new_password_display));
            exit();
        } else {
            setFlash('error', 'Failed to reset password.');
        }
    } else {
        setFlash('error', 'Invalid User ID or Password.');
    }
    
    // Reload the page if there was an error
    header('Location: view.php?id=' . $id);
    exit();
}

// Grab the new password from the URL if it was just reset
if (isset($_GET['new_pw']) && !empty($_GET['new_pw'])) {
    $new_password_display = urldecode($_GET['new_pw']);
}

// =============================================
// FETCH STUDENT DATA
// =============================================
$stmt = $pdo->prepare("
    SELECT s.*, d.department_name 
    FROM admission_students s 
    LEFT JOIN departments d ON s.program_id = d.department_id 
    WHERE s.id = ?
");
$stmt->execute([$id]);
$student = $stmt->fetch();

if (!$student) {
    setFlash('error', 'Student not found');
    header('Location: index.php');
    exit();
}

$status = $student['status'] ?? 'active';
?>
<div class="page-header">
    <div class="page-header-left">
        <h4><i class="fas fa-user-graduate"></i> Student Details</h4>
    </div>
    <div class="page-header-actions">
        <a href="index.php" class="btn btn-outline">
            <i class="fas fa-arrow-left"></i> Back
        </a>
        <a href="edit.php?id=<?= $student['id'] ?>" class="btn btn-primary">
            <i class="fas fa-edit"></i> Edit
        </a>
    </div>
</div>

<?php
$flash = getFlash();
if ($flash): ?>
    <div class="alert alert-<?= $flash['type'] ?>">
        <?= $flash['message'] ?>
    </div>
<?php endif; ?>

<div class="grid-2">
    <div class="card">
        <div class="card-header">
            <div>
                <h3>Personal Information</h3>
                <p>Student contact and demographic details</p>
            </div>
        </div>
        <div class="card-content">
            <div class="detail-row">
                <div class="detail-label">Student ID</div>
                <div class="detail-value"><strong><?= htmlspecialchars($student['student_id'] ?? 'N/A') ?></strong></div>
            </div>
            <div class="detail-row">
                <div class="detail-label">Full Name</div>
                <div class="detail-value"><?= htmlspecialchars($student['student_name'] ?? 'N/A') ?></div>
            </div>
            <div class="detail-row">
                <div class="detail-label">Father Name</div>
                <div class="detail-value"><?= htmlspecialchars($student['father_name'] ?? 'N/A') ?></div>
            </div>
            <div class="detail-row">
                <div class="detail-label">CNIC/B-Form</div>
                <div class="detail-value"><?= htmlspecialchars($student['cnic_or_bform'] ?? 'N/A') ?></div>
            </div>
            <div class="detail-row">
                <div class="detail-label">Date of Birth</div>
                <div class="detail-value"><?= isset($student['dob']) ? date('d M Y', strtotime($student['dob'])) : 'N/A' ?></div>
            </div>
            <div class="detail-row">
                <div class="detail-label">Gender</div>
                <div class="detail-value"><?= htmlspecialchars($student['gender'] ?? 'N/A') ?></div>
            </div>
            <div class="detail-row">
                <div class="detail-label">Email</div>
                <div class="detail-value"><?= htmlspecialchars($student['email'] ?? 'N/A') ?></div>
            </div>
            <div class="detail-row">
                <div class="detail-label">Contact Number</div>
                <div class="detail-value"><?= htmlspecialchars($student['contact_no'] ?? 'N/A') ?></div>
            </div>
            <div class="detail-row">
                <div class="detail-label">Address</div>
                <div class="detail-value"><?= htmlspecialchars($student['address'] ?? 'N/A') ?></div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <div>
                <h3>Academic & Enrollment Status</h3>
                <p>Department assignment and record metadata</p>
            </div>
        </div>
        <div class="card-content">
            <div class="detail-row">
                <div class="detail-label">Department</div>
                <div class="detail-value"><?= htmlspecialchars($student['department_name'] ?? 'N/A') ?></div>
            </div>
            <div class="detail-row">
                <div class="detail-label">Status</div>
                <div class="detail-value">
                    <span class="status-badge <?= strtolower($status) ?>"><?= ucfirst($status) ?></span>
                </div>
            </div>
            <?php if (!empty($student['enrollment_date'])): ?>
            <div class="detail-row">
                <div class="detail-label">Enrollment Date</div>
                <div class="detail-value"><?= date('d M Y', strtotime($student['enrollment_date'])) ?></div>
            </div>
            <?php endif; ?>
            <?php if (!empty($student['created_at'])): ?>
            <div class="detail-row">
                <div class="detail-label">Added Date</div>
                <div class="detail-value"><?= date('d M Y, h:i A', strtotime($student['created_at'])) ?></div>
            </div>
            <?php endif; ?>
            <?php if (!empty($student['application_id'])): ?>
            <div class="detail-row">
                <div class="detail-label">Application Reference</div>
                <div class="detail-value"><?= htmlspecialchars($student['application_id']) ?></div>
            </div>
            <?php endif; ?>

            <?php if (!empty($student['scholarship_id'])): 
                $sch_stmt = $pdo->prepare("SELECT scholarship_name, percentage, amount, description FROM admission_scholarships WHERE scholarship_id = ?");
                $sch_stmt->execute([$student['scholarship_id']]);
                $scholar = $sch_stmt->fetch();
            ?>
            <hr>
            <div class="detail-row">
                <div class="detail-label">Awarded Scholarship</div>
                <div class="detail-value">
                    <span style="display:inline-flex;align-items:center;gap:6px;background:#fef3c7;border:1px solid #f59e0b;border-radius:6px;padding:4px 12px;font-weight:600;color:#92400e;">
                        <i class="fas fa-award" style="color:#f59e0b;"></i>
                        <?= htmlspecialchars($scholar['scholarship_name'] ?? 'N/A') ?>
                        (<?= number_format($scholar['percentage'] ?? 0, 0) ?>%)
                    </span>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- ============================================= -->
            <!-- PASSWORD SECTION WITH ACTUAL PLAIN TEXT -->
            <!-- ============================================= -->
            <?php 
            $user_sql = "SELECT user_id, login_id, username FROM users WHERE login_id = ? OR email = ?";
            $user_stmt = $pdo->prepare($user_sql);
            $user_stmt->execute([$student['student_id'], $student['email']]);
            $user_data = $user_stmt->fetch();
            
            if ($user_data): 
            ?>
            <hr>
            <div style="background:#f0fdf4;border:1px solid #bbf7d0;border-radius:8px;padding:12px;margin-top:10px;">
                <h5 style="margin:0 0 8px 0;color:#065f46;font-size:14px;"><i class="fas fa-key"></i> Student Login Credentials</h5>
                
                <div style="display:flex;flex-direction:column;gap:8px;margin-bottom:12px;">
                    <div>
                        <div style="font-size:12px;color:#6b7280;">Login ID:</div>
                        <div style="font-weight:600;"><?= htmlspecialchars($user_data['login_id']) ?></div>
                    </div>
                    
                    <!-- THIS IS WHERE THE PASSWORD DISPLAYS -->
                    <div>
                        <div style="font-size:12px;color:#6b7280;">Active Password:</div>
                        <?php if (!empty($new_password_display)): ?>
                            <!-- Shows the new plain text password in a green badge -->
                            <div style="font-weight:700;color:#065f46;background:#dcfce7;padding:6px 10px;border-radius:4px;display:inline-block;border:1px solid #86efac;">
                                <i class="fas fa-key"></i> <?= htmlspecialchars($new_password_display) ?>
                            </div>
                        <?php else: ?>
                            <!-- Default display if never reset -->
                            <div style="font-style:italic;color:#6b7280;">
                                <i class="fas fa-lock"></i> [Encrypted - Reset the password above to view it]
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Reset Password Form -->
                <form method="POST" action="" style="display:flex;gap:10px;align-items:center;flex-wrap:wrap;border-top:1px solid #bbf7d0;padding-top:12px;">
                    <input type="hidden" name="user_id" value="<?= $user_data['user_id'] ?>">
                    <input type="text" name="new_password" placeholder="Type New Password" required style="padding:6px 10px;border:1px solid #d1d5db;border-radius:4px;font-size:13px;flex-grow:1;">
                    <button type="submit" name="action" value="reset_student_password" class="btn btn-warning" style="padding:6px 12px;background:#d97706;color:#fff;border:none;border-radius:4px;cursor:pointer;white-space:nowrap;">
                        <i class="fas fa-sync-alt"></i> Reset Password
                    </button>
                </form>
            </div>
            <?php endif; ?>
            <!-- ============================================= -->
            
        </div>
    </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>