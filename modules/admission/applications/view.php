<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Correct path for database
$db_paths = [
    '../../config/database.php',
    '../config/database.php',
    __DIR__ . '/../../config/database.php',
    __DIR__ . '/../config/database.php',
];

$db_found = false;
foreach ($db_paths as $path) {
    if (file_exists($path)) {
        require_once $path;
        $db_found = true;
        break;
    }
}

if (!$db_found) {
    die("Database configuration not found!");
}

$page_title = 'View Application';

// Try to find header
$header_paths = [
    __DIR__ . '/../../includes/header.php',
    __DIR__ . '/../includes/header.php',
    __DIR__ . '/includes/header.php',
];

$header_found = false;
foreach ($header_paths as $path) {
    if (file_exists($path)) {
        include $path;
        $header_found = true;
        break;
    }
}

if (!$header_found) {
    die("Header file not found!");
}

$id = $_GET['id'] ?? 0;

// =============================================
// GET TABLE COLUMNS TO KNOW WHAT EXISTS
// =============================================
try {
    $columns_query = "SHOW COLUMNS FROM admission_applications";
    $columns_stmt = $pdo->query($columns_query);
    $existing_columns = $columns_stmt->fetchAll(PDO::FETCH_COLUMN);
} catch (PDOException $e) {
    $existing_columns = [];
}

// =============================================
// BUILD QUERY DYNAMICALLY BASED ON EXISTING COLUMNS
// =============================================
$select_fields = "a.*";
$joins = [];

// Department join - check if program_id exists
if (in_array('program_id', $existing_columns)) {
    $select_fields .= ", d.department_name";
    $joins[] = "LEFT JOIN departments d ON a.program_id = d.department_id";
}

// Session join - check if session_id exists
if (in_array('session_id', $existing_columns)) {
    $select_fields .= ", s.session_name";
    $joins[] = "LEFT JOIN sessions s ON a.session_id = s.session_id";
}

// Semester join - check if applied_semester_id exists
if (in_array('applied_semester_id', $existing_columns)) {
    $select_fields .= ", sem.semester_name";
    $joins[] = "LEFT JOIN semesters sem ON a.applied_semester_id = sem.semester_id";
}

// Reviewer join - check if reviewed_by exists
if (in_array('reviewed_by', $existing_columns)) {
    $select_fields .= ", u.full_name as reviewer_name";
    $joins[] = "LEFT JOIN users u ON a.reviewed_by = u.user_id";
}

// Creator join - check if created_by exists (SKIP - doesn't exist in your table)
// Removed: a.created_by since it doesn't exist

// =============================================
// BUILD THE FULL QUERY
// =============================================
$join_sql = !empty($joins) ? implode(" ", $joins) : "";

// First try: Search by application_id
$sql = "SELECT $select_fields 
        FROM admission_applications a 
        $join_sql
        WHERE a.application_id = ?";

$stmt = $pdo->prepare($sql);
$stmt->execute([$id]);
$app = $stmt->fetch();

// If not found, try to get the latest application
if (!$app) {
    $sql = "SELECT $select_fields 
            FROM admission_applications a 
            $join_sql
            ORDER BY a.application_id DESC LIMIT 1";
    
    $stmt = $pdo->query($sql);
    $app = $stmt->fetch();
}

if (!$app) {
    setFlash('error', 'Application not found');
    header('Location: index.php');
    exit();
}

// Get application status
$status = $app['application_status'] ?? $app['status'] ?? 'Submitted';
$status_lower = strtolower($status);
$can_review = in_array($status_lower, ['submitted', 'pending', 'under review']);

// Check if student was created from this application
$student = null;
try {
    // Check if admission_students table exists
    $table_check = $pdo->query("SHOW TABLES LIKE 'admission_students'");
    if ($table_check->rowCount() > 0) {
        $student_stmt = $pdo->prepare("
            SELECT student_id, student_name, status 
            FROM admission_students 
            WHERE application_id = ?
        ");
        $student_stmt->execute([$app['application_id']]);
        $student = $student_stmt->fetch();
    }
} catch (PDOException $e) {
    // Table might not exist
}

// Get document uploads if any
$documents = [];
try {
    $table_check = $pdo->query("SHOW TABLES LIKE 'admission_documents'");
    if ($table_check->rowCount() > 0) {
        $doc_stmt = $pdo->prepare("
            SELECT * FROM admission_documents 
            WHERE application_id = ? 
            ORDER BY uploaded_at DESC
        ");
        $doc_stmt->execute([$app['application_id']]);
        $documents = $doc_stmt->fetchAll();
    }
} catch (PDOException $e) {
    // Documents table might not exist
}

$flash = getFlash();
if ($flash): ?>
    <div class="alert alert-<?= $flash['type'] === 'success' ? 'success' : 'danger' ?>">
        <i class="fas fa-<?= $flash['type'] === 'success' ? 'check-circle' : 'exclamation-circle' ?>"></i>
        <?= $flash['message'] ?>
    </div>
<?php endif; ?>

<!-- PAGE HEADER -->
<div class="page-header">
    <div class="page-header-left">
        <h4><i class="fas fa-file-alt" style="color:#2563eb;"></i> Application Details</h4>
        <p style="margin:2px 0 0 0;font-size:14px;color:#6b7280;">
            Application #<?= htmlspecialchars($app['application_id'] ?? 'N/A') ?>
            <span class="status-badge <?= $status_lower ?>" style="margin-left:10px;">
                <?= htmlspecialchars(ucfirst($status)) ?>
            </span>
        </p>
    </div>
    <div class="page-header-actions" style="display:flex;gap:10px;flex-wrap:wrap;">
        <!-- Print Button -->
        <button onclick="window.print()" class="btn btn-outline" style="padding:8px 16px;border:1px solid #e2e8f0;border-radius:8px;text-decoration:none;color:#4b5563;background:transparent;cursor:pointer;">
            <i class="fas fa-print"></i> Print
        </button>
        
        <!-- Back Button -->
        <a href="index.php" class="btn btn-ghost" style="padding:8px 16px;border-radius:8px;text-decoration:none;color:#4b5563;">
            <i class="fas fa-arrow-left"></i> Back to List
        </a>
        
        <!-- Review Button (Only for pending applications) -->
        <?php if($can_review): ?>
        <a href="review.php?id=<?= $app['application_id'] ?>" 
           class="btn btn-primary" 
           style="padding:8px 20px;background:#2563eb;color:#fff;border-radius:8px;text-decoration:none;display:inline-flex;align-items:center;gap:6px;">
            <i class="fas fa-check-circle"></i> Review Application
        </a>
        <?php endif; ?>
    </div>
</div>

<!-- APPLICATION STATUS BANNER -->
<?php if($can_review): ?>
<div style="background:#fffbeb;border:1px solid #fcd34d;border-radius:8px;padding:16px 20px;margin-bottom:24px;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px;">
    <div style="display:flex;align-items:center;gap:12px;">
        <i class="fas fa-clock" style="font-size:20px;color:#f59e0b;"></i>
        <div>
            <div style="font-weight:600;color:#92400e;">This application is pending review</div>
            <div style="font-size:13px;color:#78350f;">Please review the application and make a decision.</div>
        </div>
    </div>
    <a href="review.php?id=<?= $app['application_id'] ?>" 
       class="btn btn-primary" 
       style="background:#f59e0b;color:#fff;padding:8px 24px;border-radius:8px;text-decoration:none;display:inline-flex;align-items:center;gap:6px;border:none;">
        <i class="fas fa-check-circle"></i> Review Now
    </a>
</div>
<?php elseif(in_array($status_lower, ['approved', 'admitted'])): ?>
<div style="background:#f0fdf4;border:1px solid #86efac;border-radius:8px;padding:16px 20px;margin-bottom:24px;display:flex;align-items:center;gap:12px;">
    <i class="fas fa-check-circle" style="font-size:20px;color:#10b981;"></i>
    <div>
        <div style="font-weight:600;color:#065f46;">Application Approved</div>
        <div style="font-size:13px;color:#047857;">
            This application has been approved and the student has been enrolled.
            <?php if($student): ?>
                <strong>Student ID:</strong> <?= htmlspecialchars($student['student_id']) ?>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php elseif($status_lower == 'rejected'): ?>
<div style="background:#fff1f2;border:1px solid #fca5a5;border-radius:8px;padding:16px 20px;margin-bottom:24px;display:flex;align-items:center;gap:12px;">
    <i class="fas fa-times-circle" style="font-size:20px;color:#ef4444;"></i>
    <div>
        <div style="font-weight:600;color:#991b1b;">Application Rejected</div>
        <div style="font-size:13px;color:#7f1d1d;">
            This application has been rejected.
            <?php if(!empty($app['rejection_reason'])): ?>
                <br><strong>Reason:</strong> <?= htmlspecialchars($app['rejection_reason']) ?>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- MAIN CONTENT - TWO COLUMN LAYOUT -->
<div style="display:grid;grid-template-columns:1fr 1fr;gap:24px;">

    <!-- LEFT COLUMN: Personal Information -->
    <div class="card">
        <div class="card-header" style="border-bottom:1px solid #e5e7eb;padding:16px 20px;">
            <div>
                <h3 style="font-size:16px;font-weight:600;margin:0;color:#111827;">
                    <i class="fas fa-user" style="color:#2563eb;"></i> Personal Information
                </h3>
                <p style="font-size:13px;color:#6b7280;margin:4px 0 0 0;">Applicant's basic profile details</p>
            </div>
        </div>
        <div class="card-content" style="padding:20px;">
            <div class="detail-row">
                <div class="detail-label">Application No</div>
                <div class="detail-value"><strong><?= htmlspecialchars($app['application_id'] ?? 'N/A') ?></strong></div>
            </div>
            <div class="detail-row">
                <div class="detail-label">Full Name</div>
                <div class="detail-value" style="font-weight:600;font-size:15px;color:#111827;">
                    <?= htmlspecialchars($app['full_name'] ?? 'N/A') ?>
                </div>
            </div>
            <div class="detail-row">
                <div class="detail-label">Father Name</div>
                <div class="detail-value"><?= htmlspecialchars($app['father_name'] ?? 'N/A') ?></div>
            </div>
            <div class="detail-row">
                <div class="detail-label">CNIC/B-Form</div>
                <div class="detail-value">
                    <?= htmlspecialchars($app['cnic'] ?? $app['cnic_or_bform'] ?? 'N/A') ?>
                    <?php if(!empty($app['cnic']) && strlen($app['cnic']) == 13): ?>
                        <span style="font-size:11px;color:#6b7280;margin-left:8px;">
                            (<?= substr($app['cnic'], 0, 5) . '-' . substr($app['cnic'], 5, 7) . '-' . substr($app['cnic'], 12, 1) ?>)
                        </span>
                    <?php endif; ?>
                </div>
            </div>
            <div class="detail-row">
                <div class="detail-label">Date of Birth</div>
                <div class="detail-value">
                    <?php 
                    $dob = $app['dob'] ?? null;
                    if($dob) {
                        $age = date_diff(date_create($dob), date_create('today'))->y;
                        echo date('d M Y', strtotime($dob)) . ' <span style="font-size:12px;color:#6b7280;">(' . $age . ' years)</span>';
                    } else {
                        echo 'N/A';
                    }
                    ?>
                </div>
            </div>
            <div class="detail-row">
                <div class="detail-label">Gender</div>
                <div class="detail-value">
                    <?php 
                    $gender = $app['gender'] ?? 'N/A';
                    $gender_icon = $gender == 'Male' ? 'fa-mars' : ($gender == 'Female' ? 'fa-venus' : 'fa-genderless');
                    ?>
                    <i class="fas <?= $gender_icon ?>" style="color:#6b7280;"></i>
                    <?= htmlspecialchars($gender) ?>
                </div>
            </div>
            <div class="detail-row">
                <div class="detail-label">Email</div>
                <div class="detail-value">
                    <a href="mailto:<?= htmlspecialchars($app['email'] ?? '') ?>" style="color:#2563eb;text-decoration:none;">
                        <?= htmlspecialchars($app['email'] ?? 'N/A') ?>
                    </a>
                </div>
            </div>
            <div class="detail-row">
                <div class="detail-label">Contact No</div>
                <div class="detail-value">
                    <a href="tel:<?= htmlspecialchars($app['contact_no'] ?? $app['phone'] ?? '') ?>" style="color:#2563eb;text-decoration:none;">
                        <?= htmlspecialchars($app['contact_no'] ?? $app['phone'] ?? 'N/A') ?>
                    </a>
                </div>
            </div>
            <div class="detail-row">
                <div class="detail-label">Address</div>
                <div class="detail-value"><?= htmlspecialchars($app['address'] ?? 'N/A') ?></div>
            </div>
        </div>
    </div>

    <!-- RIGHT COLUMN: Academic Information -->
    <div class="card">
        <div class="card-header" style="border-bottom:1px solid #e5e7eb;padding:16px 20px;">
            <div>
                <h3 style="font-size:16px;font-weight:600;margin:0;color:#111827;">
                    <i class="fas fa-graduation-cap" style="color:#2563eb;"></i> Academic Information
                </h3>
                <p style="font-size:13px;color:#6b7280;margin:4px 0 0 0;">Program and processing status</p>
            </div>
        </div>
        <div class="card-content" style="padding:20px;">
            <div class="detail-row">
                <div class="detail-label">Department</div>
                <div class="detail-value">
                    <span style="background:#eff6ff;color:#2563eb;padding:2px 12px;border-radius:12px;font-size:13px;">
                        <?= htmlspecialchars($app['department_name'] ?? $app['program'] ?? 'N/A') ?>
                    </span>
                </div>
            </div>
            <div class="detail-row">
                <div class="detail-label">Program</div>
                <div class="detail-value">
                    <?= htmlspecialchars($app['program'] ?? $app['department_name'] ?? 'N/A') ?>
                    <?php if(!empty($app['program_id'])): ?>
                        <span style="font-size:11px;color:#6b7280;">(ID: <?= $app['program_id'] ?>)</span>
                    <?php endif; ?>
                </div>
            </div>
            <div class="detail-row">
                <div class="detail-label">Previous Degree</div>
                <div class="detail-value"><?= htmlspecialchars($app['previous_degree'] ?? 'N/A') ?></div>
            </div>
            <div class="detail-row">
                <div class="detail-label">Academic Marks</div>
                <div class="detail-value">
                    <?php 
                    $obtained = $app['obtained_marks'] ?? null;
                    $total = $app['total_marks'] ?? null;
                    if($obtained && $total) {
                        $percentage = ($obtained / $total) * 100;
                        echo number_format($obtained, 2) . ' / ' . number_format($total, 2);
                        echo ' <span style="font-weight:600;color:#2563eb;">(' . number_format($percentage, 2) . '%)</span>';
                    } else {
                        echo 'N/A';
                    }
                    ?>
                </div>
            </div>
            <div class="detail-row">
                <div class="detail-label">Session</div>
                <div class="detail-value"><?= htmlspecialchars($app['session_name'] ?? 'N/A') ?></div>
            </div>
            <div class="detail-row">
                <div class="detail-label">Semester</div>
                <div class="detail-value"><?= htmlspecialchars($app['semester_name'] ?? 'N/A') ?></div>
            </div>
            <div class="detail-row">
                <div class="detail-label">Submitted Date</div>
                <div class="detail-value">
                    <?php 
                    $date = $app['submitted_at'] ?? $app['applied_date'] ?? $app['created_at'] ?? null;
                    if($date) {
                        echo date('d M Y, h:i A', strtotime($date));
                    } else {
                        echo 'N/A';
                    }
                    ?>
                </div>
            </div>
            
            <!-- Review Information -->
            <?php if(!empty($app['reviewer_name']) || !empty($app['reviewed_at'])): ?>
            <hr style="border:none;border-top:1px solid #e5e7eb;margin:16px 0;">
            <h4 style="font-size:13px;font-weight:600;color:#6b7280;text-transform:uppercase;letter-spacing:0.05em;margin:0 0 12px 0;">
                <i class="fas fa-check-double" style="color:#2563eb;"></i> Review Information
            </h4>
            <div class="detail-row">
                <div class="detail-label">Reviewed By</div>
                <div class="detail-value"><?= htmlspecialchars($app['reviewer_name'] ?? 'N/A') ?></div>
            </div>
            <div class="detail-row">
                <div class="detail-label">Review Date</div>
                <div class="detail-value"><?= isset($app['reviewed_at']) ? date('d M Y, h:i A', strtotime($app['reviewed_at'])) : 'N/A' ?></div>
            </div>
            <?php endif; ?>
            
            <!-- Rejection Reason -->
            <?php if(!empty($app['rejection_reason'])): ?>
            <div class="detail-row" style="border-left:3px solid #ef4444;padding-left:12px;background:#fff1f2;margin-top:8px;border-radius:4px;">
                <div class="detail-label" style="color:#991b1b;">Rejection Reason</div>
                <div class="detail-value" style="color:#991b1b;"><?= htmlspecialchars($app['rejection_reason']) ?></div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- DOCUMENTS SECTION (if available) -->
<?php if(!empty($documents)): ?>
<div class="card" style="margin-top:24px;">
    <div class="card-header" style="border-bottom:1px solid #e5e7eb;padding:16px 20px;">
        <div>
            <h3 style="font-size:16px;font-weight:600;margin:0;color:#111827;">
                <i class="fas fa-paperclip" style="color:#2563eb;"></i> Uploaded Documents
            </h3>
            <p style="font-size:13px;color:#6b7280;margin:4px 0 0 0;">Supporting documents submitted by the applicant</p>
        </div>
    </div>
    <div class="card-content" style="padding:20px;">
        <div style="display:grid;grid-template-columns:repeat(auto-fill, minmax(200px, 1fr));gap:12px;">
            <?php foreach($documents as $doc): ?>
            <div style="background:#f8fafc;border:1px solid #e5e7eb;border-radius:8px;padding:12px;display:flex;align-items:center;gap:12px;">
                <i class="fas fa-file-pdf" style="font-size:24px;color:#ef4444;"></i>
                <div style="flex:1;min-width:0;">
                    <div style="font-weight:600;font-size:13px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                        <?= htmlspecialchars($doc['document_name'] ?? 'Document') ?>
                    </div>
                    <div style="font-size:11px;color:#6b7280;">
                        <?= isset($doc['uploaded_at']) ? date('d M Y', strtotime($doc['uploaded_at'])) : '' ?>
                    </div>
                </div>
                <a href="<?= htmlspecialchars($doc['file_path'] ?? '#') ?>" target="_blank" 
                   style="color:#2563eb;text-decoration:none;padding:4px 8px;border-radius:4px;background:#eff6ff;">
                    <i class="fas fa-download"></i>
                </a>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- STUDENT RECORD LINK (if approved) -->
<?php if($student): ?>
<div class="card" style="margin-top:24px;border-color:#86efac;">
    <div class="card-header" style="border-bottom:1px solid #86efac;padding:16px 20px;background:#f0fdf4;">
        <div>
            <h3 style="font-size:16px;font-weight:600;margin:0;color:#065f46;">
                <i class="fas fa-user-graduate" style="color:#10b981;"></i> Student Record
            </h3>
            <p style="font-size:13px;color:#047857;margin:4px 0 0 0;">This application has been converted to a student record</p>
        </div>
    </div>
    <div class="card-content" style="padding:20px;">
        <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:16px;">
            <div>
                <div style="font-size:12px;color:#6b7280;">Student ID</div>
                <div style="font-weight:600;font-size:16px;color:#065f46;"><?= htmlspecialchars($student['student_id']) ?></div>
            </div>
            <div>
                <div style="font-size:12px;color:#6b7280;">Student Name</div>
                <div style="font-weight:600;color:#111827;"><?= htmlspecialchars($student['student_name']) ?></div>
            </div>
            <div>
                <div style="font-size:12px;color:#6b7280;">Status</div>
                <div style="font-weight:600;color:#10b981;"><?= htmlspecialchars(ucfirst($student['status'] ?? 'Active')) ?></div>
            </div>
        </div>
        <div style="margin-top:12px;padding-top:12px;border-top:1px solid #e5e7eb;">
            <a href="../students/view.php?id=<?= $student['student_id'] ?>" 
               style="display:inline-flex;align-items:center;gap:6px;color:#2563eb;text-decoration:none;font-weight:500;">
                <i class="fas fa-arrow-right"></i> View Full Student Record
            </a>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- PRINT STYLES -->
<style>
@media print {
    .page-header-actions, .btn, .btn-primary, .btn-outline, .btn-ghost, .btn-warning {
        display: none !important;
    }
    .card {
        break-inside: avoid;
        page-break-inside: avoid;
        border: 1px solid #ddd !important;
        box-shadow: none !important;
    }
    body {
        background: white !important;
    }
    .status-banner {
        display: none !important;
    }
    .detail-row {
        padding: 4px 0 !important;
    }
}

/* Responsive Design */
@media (max-width: 768px) {
    .grid-2 {
        grid-template-columns: 1fr !important;
    }
    .detail-row {
        flex-direction: column;
        padding: 8px 0;
    }
    .detail-label {
        width: 100%;
        font-size: 12px;
        color: #6b7280;
        margin-bottom: 2px;
    }
    .detail-value {
        font-size: 14px;
    }
    .page-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 12px;
    }
    .page-header-actions {
        width: 100%;
        flex-wrap: wrap;
    }
    .page-header-actions .btn,
    .page-header-actions a {
        flex: 1;
        min-width: 100px;
        justify-content: center;
    }
}

/* Status Badge Colors */
.status-badge {
    display: inline-block;
    padding: 4px 14px;
    border-radius: 12px;
    font-size: 12px;
    font-weight: 500;
    text-transform: capitalize;
}
.status-badge.submitted, 
.status-badge.pending, 
.status-badge.under-review {
    background: #fffbeb;
    color: #f59e0b;
}
.status-badge.approved, 
.status-badge.admitted {
    background: #f0fdf4;
    color: #10b981;
}
.status-badge.rejected {
    background: #fff1f2;
    color: #ef4444;
}

.detail-row {
    display: flex;
    padding: 8px 0;
    border-bottom: 1px solid #f3f4f6;
}
.detail-row:last-child {
    border-bottom: none;
}
.detail-label {
    font-weight: 500;
    color: #6b7280;
    width: 140px;
    font-size: 13px;
    flex-shrink: 0;
}
.detail-value {
    flex: 1;
    color: #111827;
    font-size: 14px;
    word-break: break-word;
}
</style>

<?php
// Try to find footer
$footer_paths = [
    __DIR__ . '/../../includes/footer.php',
    __DIR__ . '/../includes/footer.php',
    __DIR__ . '/includes/footer.php',
];

foreach ($footer_paths as $path) {
    if (file_exists($path)) {
        include $path;
        break;
    }
}
?>