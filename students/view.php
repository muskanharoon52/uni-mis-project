<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
requireLogin();

require_once __DIR__ . '/../includes/header.php';

$page_title = 'Student Details';
$conn = getConnection();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) {
    header("Location: index.php?error=Invalid student ID");
    exit;
}

// Get student details with all joins
$query = "SELECT s.*, 
          p.program_name,
          p.program_code,
          ses.session_name,
          sem.semester_name,
          a.application_status,
          a.temp_application_no
          FROM students s
          LEFT JOIN programs p ON s.program_id = p.program_id
          LEFT JOIN sessions ses ON s.current_session_id = ses.session_id
          LEFT JOIN semesters sem ON s.current_semester_id = sem.semester_id
          LEFT JOIN admission_applications a ON s.application_id = a.application_id
          WHERE s.student_id = ?";

// Prepare and execute with error handling
$stmt = $conn->prepare($query);

// Check if prepare was successful
if ($stmt === false) {
    die("Error in query: " . $conn->error);
}

$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$student = $result->fetch_assoc();

if (!$student) {
    header("Location: index.php?error=Student not found");
    exit;
}
$stmt->close();

include __DIR__ . '/../includes/navbar.php';
?>

<style>
    .profile-avatar-large {
        width: 120px;
        height: 120px;
        border-radius: 50%;
        background: #3498db;
        color: white;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 48px;
        font-weight: 700;
        margin: 0 auto 15px;
    }
    
    .detail-section {
        background: white;
        padding: 20px;
        border-radius: 10px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        margin-bottom: 20px;
    }
    
    .detail-label {
        font-weight: 600;
        color: #7f8c8d;
        font-size: 13px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    
    .detail-value {
        font-size: 16px;
        color: #2c3e50;
        margin-top: 3px;
    }
    
    .status-badge {
        padding: 4px 12px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 500;
    }
    
    .status-active {
        background: #d4edda;
        color: #155724;
    }
    
    .status-freeze {
        background: #fff3cd;
        color: #856404;
    }
    
    .status-graduated {
        background: #cce5ff;
        color: #004085;
    }
    
    .status-dropped {
        background: #f8d7da;
        color: #721c24;
    }
    
    .status-suspended {
        background: #e2e3e5;
        color: #383d41;
    }
    
    .info-row {
        display: flex;
        padding: 8px 0;
        border-bottom: 1px solid #f8f9fa;
    }
    
    .info-label {
        width: 150px;
        font-weight: 500;
        color: #7f8c8d;
        font-size: 14px;
    }
    
    .info-value {
        flex: 1;
        color: #2c3e50;
    }
</style>

<div class="container-fluid">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4><i class="fas fa-user-graduate"></i> Student Details</h4>
        <div>
            <a href="edit.php?id=<?php echo $id; ?>" class="btn btn-primary">
                <i class="fas fa-edit"></i> Edit
            </a>
            <a href="index.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back
            </a>
        </div>
    </div>

    <?php if (isset($_GET['success'])): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i> 
            <?php echo isset($_GET['action']) ? ucfirst($_GET['action']) : 'Operation'; ?> successful!
        </div>
    <?php endif; ?>

    <?php if (isset($_GET['error'])): ?>
        <div class="alert alert-danger">
            <i class="fas fa-exclamation-circle"></i> 
            <?php echo htmlspecialchars($_GET['error']); ?>
        </div>
    <?php endif; ?>

    <div class="row">
        <!-- Left Column - Profile -->
        <div class="col-md-4">
            <div class="detail-section text-center">
                <div class="profile-avatar-large">
                    <?php echo strtoupper(substr($student['full_name'], 0, 1)); ?>
                </div>
                <h5><?php echo htmlspecialchars($student['full_name']); ?></h5>
                <p class="text-muted">Roll No: <?php echo htmlspecialchars($student['roll_no'] ?? 'N/A'); ?></p>
                <span class="status-badge status-<?php echo strtolower($student['status']); ?>">
                    <?php echo $student['status']; ?>
                </span>
                <hr>
                <p><i class="fas fa-envelope"></i> <?php echo htmlspecialchars($student['email'] ?? 'N/A'); ?></p>
                <p><i class="fas fa-phone"></i> <?php echo htmlspecialchars($student['contact_no'] ?? 'N/A'); ?></p>
                <?php if (!empty($student['application_status'])): ?>
                <p><i class="fas fa-file-alt"></i> App Status: <?php echo htmlspecialchars($student['application_status']); ?></p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Right Column - Details -->
        <div class="col-md-8">
            <!-- Personal Information -->
            <div class="detail-section">
                <h6><i class="fas fa-user text-primary"></i> Personal Information</h6>
                <hr>
                <div class="info-row">
                    <div class="info-label">Full Name</div>
                    <div class="info-value"><?php echo htmlspecialchars($student['full_name']); ?></div>
                </div>
                <div class="info-row">
                    <div class="info-label">Father's Name</div>
                    <div class="info-value"><?php echo htmlspecialchars($student['father_name'] ?? 'N/A'); ?></div>
                </div>
                <div class="info-row">
                    <div class="info-label">CNIC / B-Form</div>
                    <div class="info-value"><?php echo htmlspecialchars($student['cnic_or_bform'] ?? 'N/A'); ?></div>
                </div>
                <div class="info-row">
                    <div class="info-label">Date of Birth</div>
                    <div class="info-value"><?php echo $student['dob'] ? date('d M, Y', strtotime($student['dob'])) : 'N/A'; ?></div>
                </div>
                <div class="info-row">
                    <div class="info-label">Gender</div>
                    <div class="info-value"><?php echo htmlspecialchars($student['gender'] ?? 'N/A'); ?></div>
                </div>
                <div class="info-row">
                    <div class="info-label">Contact</div>
                    <div class="info-value"><?php echo htmlspecialchars($student['contact_no'] ?? 'N/A'); ?></div>
                </div>
                <div class="info-row">
                    <div class="info-label">Email</div>
                    <div class="info-value"><?php echo htmlspecialchars($student['email'] ?? 'N/A'); ?></div>
                </div>
                <div class="info-row">
                    <div class="info-label">Address</div>
                    <div class="info-value"><?php echo htmlspecialchars($student['address'] ?? 'N/A'); ?></div>
                </div>
            </div>

            <!-- Academic Information -->
            <div class="detail-section">
                <h6><i class="fas fa-graduation-cap text-success"></i> Academic Information</h6>
                <hr>
                <div class="info-row">
                    <div class="info-label">Roll Number</div>
                    <div class="info-value"><strong><?php echo htmlspecialchars($student['roll_no'] ?? 'N/A'); ?></strong></div>
                </div>
                <div class="info-row">
                    <div class="info-label">Program</div>
                    <div class="info-value"><?php echo htmlspecialchars($student['program_name'] ?? 'N/A'); ?></div>
                </div>
                <div class="info-row">
                    <div class="info-label">Program Code</div>
                    <div class="info-value"><?php echo htmlspecialchars($student['program_code'] ?? 'N/A'); ?></div>
                </div>
                <div class="info-row">
                    <div class="info-label">Current Session</div>
                    <div class="info-value"><?php echo htmlspecialchars($student['session_name'] ?? 'N/A'); ?></div>
                </div>
                <div class="info-row">
                    <div class="info-label">Current Semester</div>
                    <div class="info-value"><?php echo htmlspecialchars($student['semester_name'] ?? 'N/A'); ?></div>
                </div>
                <div class="info-row">
                    <div class="info-label">Batch Year</div>
                    <div class="info-value"><?php echo htmlspecialchars($student['batch_year'] ?? 'N/A'); ?></div>
                </div>
                <div class="info-row">
                    <div class="info-label">Admission Date</div>
                    <div class="info-value"><?php echo $student['admission_date'] ? date('d M, Y', strtotime($student['admission_date'])) : 'N/A'; ?></div>
                </div>
                <div class="info-row">
                    <div class="info-label">Application Status</div>
                    <div class="info-value">
                        <span class="status-badge status-<?php echo strtolower($student['application_status'] ?? 'pending'); ?>">
                            <?php echo htmlspecialchars($student['application_status'] ?? 'N/A'); ?>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>