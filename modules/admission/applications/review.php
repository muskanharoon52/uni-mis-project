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

$page_title = 'Review Application';

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

// Get application ID
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

// Determine the correct column names for your table
$cnic_column = in_array('cnic', $existing_columns) ? 'cnic' : 
               (in_array('cnic_or_bform', $existing_columns) ? 'cnic_or_bform' : 'cnic');
$phone_column = in_array('phone', $existing_columns) ? 'phone' : 
                (in_array('contact_no', $existing_columns) ? 'contact_no' : 'phone');
$status_column = in_array('application_status', $existing_columns) ? 'application_status' : 'status';
$address_column = in_array('address', $existing_columns) ? 'address' : 
                  (in_array('residential_address', $existing_columns) ? 'residential_address' : 'address');

// =============================================
// BUILD QUERY DYNAMICALLY
// =============================================
$select_fields = "a.*";
$joins = [];

// Department join - check if program_id exists
if (in_array('program_id', $existing_columns)) {
    $select_fields .= ", d.department_name, d.department_id";
    $joins[] = "LEFT JOIN departments d ON a.program_id = d.department_id";
}

$join_sql = !empty($joins) ? implode(" ", $joins) : "";

// Fetch application details
$sql = "SELECT $select_fields 
        FROM admission_applications a 
        $join_sql
        WHERE a.application_id = ?";

$stmt = $pdo->prepare($sql);
$stmt->execute([$id]);
$app = $stmt->fetch();

if (!$app) {
    $sql = "SELECT $select_fields 
            FROM admission_applications a 
            $join_sql
            ORDER BY a.application_id DESC LIMIT 1";
    
    $stmt = $pdo->query($sql);
    $app = $stmt->fetch();
}

if (!$app) {
    setFlash('error', 'Application not found!');
    header('Location: index.php');
    exit();
}

// =============================================
// GET SESSIONS
// =============================================
$sessions = [];
try {
    $session_stmt = $pdo->query("SELECT * FROM academic_sessions WHERE status = 'active' ORDER BY start_date DESC");
    $sessions = $session_stmt->fetchAll();
} catch (PDOException $e) {
    // Table might not exist
}

// =============================================
// GET SECTIONS
// =============================================
$sections = [];
try {
    $section_stmt = $pdo->query("SELECT section_id, section_name, program_id, semester_id, capacity FROM sections WHERE status = 'Active' ORDER BY section_name");
    $sections = $section_stmt->fetchAll();
} catch (PDOException $e) {
    // Table might not exist
}

// =============================================
// GET FEE STRUCTURES (UPDATED FOR YOUR SCHEMA)
// =============================================
$fee_structures = [];
try {
    $fee_struct_stmt = $pdo->query("
        SELECT fs.*, d.department_name 
        FROM fee_structures fs 
        LEFT JOIN departments d ON fs.department_id = d.department_id 
        WHERE fs.status = 'active' 
        ORDER BY fs.department_id
    ");
    $fee_structures = $fee_struct_stmt->fetchAll();
} catch (PDOException $e) {
    // Table might not exist
}

// =============================================
// GET STUDENT ADMISSION DETAILS
// =============================================
$admission_details = null;
try {
    $admission_stmt = $pdo->prepare("SELECT * FROM student_admission_details WHERE application_id = ?");
    $admission_stmt->execute([$app['application_id']]);
    $admission_details = $admission_stmt->fetch();
} catch (PDOException $e) {
    // Table might not exist
}

// =============================================
// CHECK IF FEE TABLE EXISTS
// =============================================
try {
    $table_check = $pdo->query("SHOW TABLES LIKE 'admission_fees'");
    $fee_table_exists = $table_check->rowCount() > 0;
} catch (PDOException $e) {
    $fee_table_exists = false;
}

// Check if fee record exists for this application
$fee_record = null;
$fee_exists = false;

if ($fee_table_exists) {
    try {
        $fee_check = $pdo->prepare("SELECT * FROM admission_fees WHERE application_id = ?");
        $fee_check->execute([$app['application_id']]);
        $fee_record = $fee_check->fetch();
        $fee_exists = ($fee_record !== false);
    } catch (PDOException $e) {
        $fee_exists = false;
    }
}

// =============================================
// HANDLE FORM SUBMISSION
// =============================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $action = $_POST['action'] ?? '';
        $rejection_reason = sanitize($_POST['rejection_reason'] ?? '');
        
        // Start transaction
        $pdo->beginTransaction();
        
        // =============================================
        // SAVE ADMISSION DETAILS
        // =============================================
        if ($action === 'save_admission_details') {
            $session_id = $_POST['session_id'] ?? null;
            $section_id = $_POST['section_id'] ?? null;
            $semester_id = $_POST['semester_id'] ?? null;
            $fee_structure_id = $_POST['fee_structure_id'] ?? null;
            $fee_structure_ids = isset($_POST['fee_structure_ids']) ? implode(',', array_map('intval', $_POST['fee_structure_ids'])) : '';
            $scholarship_id = !empty($_POST['scholarship_id']) ? (int)$_POST['scholarship_id'] : null;
            $start_date = $_POST['start_date'] ?? date('Y-m-d');
            $end_date = $_POST['end_date'] ?? date('Y-m-d', strtotime('+4 months'));
            $admission_date = $_POST['admission_date'] ?? date('Y-m-d');
            
            // Check if admission details exist
            $check_admission = $pdo->prepare("SELECT * FROM student_admission_details WHERE application_id = ?");
            $check_admission->execute([$app['application_id']]);
            $existing_admission = $check_admission->fetch();
            
            if ($existing_admission) {
                $update_admission = $pdo->prepare("
                    UPDATE student_admission_details SET 
                        session_id = ?,
                        section_id = ?,
                        selected_fee_ids = ?,
                        scholarship_id = ?,
                        admission_date = ?,
                        start_date = ?,
                        end_date = ?,
                        admission_year = YEAR(?)
                    WHERE application_id = ?
                ");
                $update_admission->execute([
                    $session_id,
                    $section_id,
                    $fee_structure_ids,
                    $scholarship_id,
                    $admission_date,
                    $start_date,
                    $end_date,
                    $start_date,
                    $app['application_id']
                ]);
            } else {
                $insert_admission = $pdo->prepare("
                    INSERT INTO student_admission_details 
                    (application_id, session_id, section_id, selected_fee_ids, scholarship_id,
                     admission_date, start_date, end_date, admission_year, created_by) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, YEAR(?), ?)
                ");
                $insert_admission->execute([
                    $app['application_id'],
                    $session_id,
                    $section_id,
                    $fee_structure_ids,
                    $scholarship_id,
                    $admission_date,
                    $start_date,
                    $end_date,
                    $start_date,
                    $_SESSION['user_id'] ?? 1
                ]);
            }
            
            // Update application with semester info
            $update_app = $pdo->prepare("
                UPDATE admission_applications SET 
                    session_id = ?
                WHERE application_id = ?
            ");
            $update_app->execute([$session_id, $app['application_id']]);
            
            $pdo->commit();
            
            setFlash('success', 
                '✅ Admission Details Saved Successfully!<br>
                <strong>Session:</strong> ' . ($session_id ? 'Selected' : 'Not Set') . '<br>
                <strong>Section:</strong> ' . ($section_id ? 'Selected' : 'Not Set') . '<br>
                <strong>Semester:</strong> ' . ($semester_id ? 'Selected' : 'Not Set') . '<br>
                <strong>Start Date:</strong> ' . date('d M Y', strtotime($start_date))
            );
            header('Location: review.php?id=' . $app['application_id']);
            exit();
        }
        
        // =============================================
        // CASE 1: APPROVE WITH FEE
        // =============================================
        if ($action === 'approve_with_fee') {
            // Check if fee table exists, if not create it
            if (!$fee_table_exists) {
                $create_table = "
                    CREATE TABLE IF NOT EXISTS `admission_fees` (
                        `id` int(11) NOT NULL AUTO_INCREMENT,
                        `application_id` varchar(50) NOT NULL,
                        `student_name` varchar(100) NOT NULL,
                        `cnic` varchar(15) DEFAULT NULL,
                        `email` varchar(100) DEFAULT NULL,
                        `phone` varchar(15) DEFAULT NULL,
                        `program` varchar(100) DEFAULT NULL,
                        `fee_amount` decimal(10,2) NOT NULL DEFAULT 1000.00,
                        `fee_challan_no` varchar(50) NOT NULL,
                        `status` enum('pending','paid','cancelled') DEFAULT 'pending',
                        `payment_method` varchar(50) DEFAULT NULL,
                        `payment_reference` varchar(100) DEFAULT NULL,
                        `paid_at` datetime DEFAULT NULL,
                        `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
                        `due_date` datetime DEFAULT NULL,
                        PRIMARY KEY (`id`),
                        UNIQUE KEY `fee_challan_no` (`fee_challan_no`),
                        KEY `application_id` (`application_id`)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
                ";
                $pdo->exec($create_table);
                $fee_table_exists = true;
            }
            
            // Check if fee already exists
            $fee_check = $pdo->prepare("SELECT * FROM admission_fees WHERE application_id = ?");
            $fee_check->execute([$app['application_id']]);
            $existing_fee = $fee_check->fetch();
            
            if (!$existing_fee) {
                // Get fee amount from fee structure if selected
                $fee_amount = 1000;
                $fee_structure_id = $_POST['fee_structure_id'] ?? null;
                if ($fee_structure_id) {
                    $fee_struct_stmt = $pdo->prepare("SELECT amount FROM fee_structures WHERE fee_structure_id = ?");
                    $fee_struct_stmt->execute([$fee_structure_id]);
                    $fee_struct = $fee_struct_stmt->fetch();
                    if ($fee_struct) {
                        $fee_amount = $fee_struct['amount'];
                    }
                }
                
                $fee_challan_no = 'CH-' . date('Y') . '-' . str_pad(rand(1, 99999), 5, '0', STR_PAD_LEFT);
                
                // Get the correct values based on column names
                $cnic_value = $app[$cnic_column] ?? '';
                $phone_value = $app[$phone_column] ?? '';
                
                $fee_stmt = $pdo->prepare("
                    INSERT INTO admission_fees 
                    (application_id, student_name, cnic, email, phone, program, 
                     fee_amount, fee_challan_no, status, created_at, due_date) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pending', NOW(), DATE_ADD(NOW(), INTERVAL 7 DAY))
                ");
                $fee_stmt->execute([
                    $app['application_id'],
                    $app['full_name'],
                    $cnic_value,
                    $app['email'] ?? '',
                    $phone_value,
                    $app['program'] ?? '',
                    $fee_amount,
                    $fee_challan_no
                ]);
                
                // Update application status to "Fee Pending"
                $update_stmt = $pdo->prepare("
                    UPDATE admission_applications SET 
                        $status_column = 'Fee Pending',
                        status = 'fee_pending'
                    WHERE application_id = ?
                ");
                $update_stmt->execute([$app['application_id']]);
                
                $pdo->commit();
                
                setFlash('success', 
                    '✅ Application Approved! Fee Challan Generated.<br>
                    <strong>Fee Amount:</strong> Rs ' . number_format($fee_amount) . '<br>
                    <strong>Challan No:</strong> ' . $fee_challan_no . '<br>
                    <strong>Due Date:</strong> ' . date('d M Y', strtotime('+7 days')) . '<br><br>
                    <em>Student will be created after fee payment confirmation.</em>'
                );
                header('Location: index.php');
                exit();
            } else {
                $pdo->commit();
                setFlash('warning', 'Fee already generated for this application. Challan No: ' . $existing_fee['fee_challan_no']);
                header('Location: index.php');
                exit();
            }
        }
        
        // =============================================
        // CASE 2: CONFIRM PAYMENT & CREATE STUDENT
        // =============================================
        elseif ($action === 'confirm_payment') {
            // Check if fee table exists
            if (!$fee_table_exists) {
                throw new Exception('Fee table does not exist. Please approve with fee first.');
            }
            
            // Check fee record
            $fee_stmt = $pdo->prepare("SELECT * FROM admission_fees WHERE application_id = ?");
            $fee_stmt->execute([$app['application_id']]);
            $fee = $fee_stmt->fetch();
            
            if (!$fee) {
                // Create fee record automatically if it doesn't exist
                $fee_amount = 1000;
                $fee_challan_no = 'CH-' . date('Y') . '-' . str_pad(rand(1, 99999), 5, '0', STR_PAD_LEFT);
                
                $cnic_value = $app[$cnic_column] ?? '';
                $phone_value = $app[$phone_column] ?? '';
                
                $fee_stmt = $pdo->prepare("
                    INSERT INTO admission_fees 
                    (application_id, student_name, cnic, email, phone, program, 
                     fee_amount, fee_challan_no, status, created_at, due_date) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pending', NOW(), DATE_ADD(NOW(), INTERVAL 7 DAY))
                ");
                $fee_stmt->execute([
                    $app['application_id'],
                    $app['full_name'],
                    $cnic_value,
                    $app['email'] ?? '',
                    $phone_value,
                    $app['program'] ?? '',
                    $fee_amount,
                    $fee_challan_no
                ]);
                
                // Get the newly created fee
                $fee_stmt = $pdo->prepare("SELECT * FROM admission_fees WHERE application_id = ?");
                $fee_stmt->execute([$app['application_id']]);
                $fee = $fee_stmt->fetch();
            }
            
            if ($fee['status'] === 'paid') {
                throw new Exception('Fee already paid for this application.');
            }
            
            // Update fee status to paid
            $update_fee = $pdo->prepare("
                UPDATE admission_fees SET 
                    status = 'paid',
                    paid_at = NOW(),
                    payment_method = ?,
                    payment_reference = ?
                WHERE application_id = ?
            ");
            $update_fee->execute([
                $_POST['payment_method'] ?? 'Manual',
                $_POST['payment_reference'] ?? 'MANUAL-' . date('YmdHis'),
                $app['application_id']
            ]);
            
            // Create Student Record
            $student_id = 'STU-' . date('Y') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
            $default_password = 'student123';
            $password_hash = password_hash($default_password, PASSWORD_DEFAULT);
            $username = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', explode(' ', $app['full_name'])[0])) . rand(100, 999);
            $login_id = strval(9000 + (int)date('Y') % 1000 + rand(100, 999));
            
            // Ensure unique login_id
            while (true) {
                $check_uid = $pdo->prepare("SELECT user_id FROM users WHERE login_id = ?");
                $check_uid->execute([$login_id]);
                if (!$check_uid->fetch()) break;
                $login_id = strval(9000 + (int)date('Y') % 1000 + rand(100, 999));
            }
            
            // Get phone and cnic values
            $phone_value = $app[$phone_column] ?? '';
            $cnic_value = $app[$cnic_column] ?? '';
            $address_value = $app[$address_column] ?? '';
            
            // Create user
            $user_stmt = $pdo->prepare("
                INSERT INTO users (full_name, username, login_id, email, phone, password_hash, role_id, department_id, status)
                VALUES (?, ?, ?, ?, ?, ?, 4, ?, 'Active')
            ");
            $user_stmt->execute([
                $app['full_name'],
                $username,
                $login_id,
                $app['email'] ?? $username . '@university.edu',
                $phone_value,
                $password_hash,
                $app['program_id'] ?? 1
            ]);
            $new_user_id = (int) $pdo->lastInsertId();
            
            // Get scholarship from admission_details if set
            $award_scholarship_id = null;
            try {
                $det_stmt = $pdo->prepare("SELECT scholarship_id FROM student_admission_details WHERE application_id = ?");
                $det_stmt->execute([$app['application_id']]);
                $det_row = $det_stmt->fetch();
                if ($det_row && !empty($det_row['scholarship_id'])) {
                    $award_scholarship_id = (int)$det_row['scholarship_id'];
                }
            } catch (Exception $e) {}

            // Create student record in admission_students
            $student_stmt = $pdo->prepare("
                INSERT INTO admission_students 
                (student_id, application_id, student_name, father_name, cnic_or_bform, 
                 dob, gender, contact_no, email, address, program_id, scholarship_id, status) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'active')
            ");
            $student_stmt->execute([
                $student_id,
                $app['application_id'],
                $app['full_name'],
                $app['father_name'] ?? '',
                $cnic_value,
                $app['dob'] ?? null,
                $app['gender'] ?? 'Male',
                $phone_value,
                $app['email'] ?? '',
                $address_value,
                $app['program_id'] ?? 1,
                $award_scholarship_id
            ]);
            
            // Update application status
            $update_stmt = $pdo->prepare("
                UPDATE admission_applications SET 
                    $status_column = 'Approved',
                    status = 'approved',
                    reviewed_by = ?,
                    reviewed_at = NOW()
                WHERE application_id = ?
            ");
            $update_stmt->execute([$_SESSION['user_id'] ?? 1, $app['application_id']]);
            
            $pdo->commit();
            
            setFlash('success', 
                '✅ Payment Confirmed & Student Created!<br>
                <strong>Student ID:</strong> ' . $student_id . '<br>
                <strong>Login ID:</strong> ' . $login_id . '<br>
                <strong>Password:</strong> ' . $default_password . '<br><br>
                <em>Student can now login to the system.</em>'
            );
            header('Location: index.php');
            exit();
        }
        
        // =============================================
        // CASE 3: REJECT APPLICATION
        // =============================================
        elseif ($action === 'reject') {
            $update_stmt = $pdo->prepare("
                UPDATE admission_applications SET 
                    $status_column = 'Rejected',
                    status = 'rejected',
                    rejection_reason = ?,
                    reviewed_by = ?,
                    reviewed_at = NOW()
                WHERE application_id = ?
            ");
            $update_stmt->execute([
                $rejection_reason,
                $_SESSION['user_id'] ?? 1,
                $app['application_id']
            ]);
            
            $pdo->commit();
            
            setFlash('success', '❌ Application Rejected!');
            header('Location: index.php');
            exit();
        }
        
        // =============================================
        // CASE 4: KEEP UNDER REVIEW
        // =============================================
        elseif ($action === 'under_review') {
            $update_stmt = $pdo->prepare("
                UPDATE admission_applications SET 
                    $status_column = 'Under Review',
                    status = 'under_review'
                WHERE application_id = ?
            ");
            $update_stmt->execute([$app['application_id']]);
            
            $pdo->commit();
            
            setFlash('success', '⏳ Application kept under review.');
            header('Location: index.php');
            exit();
        }
        
    } catch (Exception $e) {
        $pdo->rollBack();
        setFlash('error', 'Database Error: ' . $e->getMessage());
        error_log('Review Error: ' . $e->getMessage());
    }
}

// Generate preview student ID
$preview_student_id = 'STU-' . date('Y') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);

// Flash messages
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
        <h4><i class="fas fa-check-circle" style="color:#2563eb;"></i> Review Application</h4>
        <p style="margin:2px 0 0 0;font-size:14px;color:#6b7280;">
            Application #<?= htmlspecialchars($app['application_id'] ?? 'N/A') ?>
            <span class="status-badge <?= strtolower($app[$status_column] ?? $app['status'] ?? 'pending') ?>" style="margin-left:10px;">
                <?= ucfirst($app[$status_column] ?? $app['status'] ?? 'Pending') ?>
            </span>
            <?php if($fee_exists && $fee_record): ?>
                <span style="margin-left:10px;font-size:12px;background:#f0fdf4;color:#065f46;padding:2px 10px;border-radius:12px;">
                    <i class="fas fa-money-bill-wave"></i> Fee: <?= ucfirst($fee_record['status']) ?>
                </span>
            <?php endif; ?>
        </p>
    </div>
    <div class="page-header-actions">
        <a href="view.php?id=<?= $app['application_id'] ?>" class="btn btn-outline">
            <i class="fas fa-eye"></i> View Details
        </a>
        <a href="index.php" class="btn btn-ghost">
            <i class="fas fa-arrow-left"></i> Back to List
        </a>
    </div>
</div>

<!-- TWO COLUMN LAYOUT -->
<div style="display:grid;grid-template-columns:1fr 1fr;gap:24px;margin-top:20px;">

    <!-- LEFT COLUMN: Application Details -->
    <div class="card">
        <div class="card-header">
            <div>
                <h3><i class="fas fa-file-alt" style="color:#2563eb;"></i> Application Summary</h3>
                <p>Details for verification prior to review</p>
            </div>
        </div>
        <div class="card-content">
            <!-- Personal Information -->
            <div style="margin-bottom:16px;">
                <h4 style="font-size:13px;font-weight:600;color:#6b7280;text-transform:uppercase;">
                    <i class="fas fa-user" style="color:#2563eb;"></i> Personal Information
                </h4>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;">
                    <div>
                        <div style="font-size:12px;color:#6b7280;">Full Name</div>
                        <div style="font-weight:600;color:#111827;"><?= htmlspecialchars($app['full_name'] ?? 'N/A') ?></div>
                    </div>
                    <div>
                        <div style="font-size:12px;color:#6b7280;">Father's Name</div>
                        <div style="font-weight:600;color:#111827;"><?= htmlspecialchars($app['father_name'] ?? 'N/A') ?></div>
                    </div>
                    <div>
                        <div style="font-size:12px;color:#6b7280;">CNIC/B-Form</div>
                        <div style="font-weight:600;color:#111827;"><?= htmlspecialchars($app[$cnic_column] ?? 'N/A') ?></div>
                    </div>
                    <div>
                        <div style="font-size:12px;color:#6b7280;">Date of Birth</div>
                        <div style="font-weight:600;color:#111827;"><?= isset($app['dob']) ? date('d M Y', strtotime($app['dob'])) : 'N/A' ?></div>
                    </div>
                    <div>
                        <div style="font-size:12px;color:#6b7280;">Gender</div>
                        <div style="font-weight:600;color:#111827;"><?= htmlspecialchars($app['gender'] ?? 'N/A') ?></div>
                    </div>
                    <div>
                        <div style="font-size:12px;color:#6b7280;">Contact</div>
                        <div style="font-weight:600;color:#111827;"><?= htmlspecialchars($app[$phone_column] ?? 'N/A') ?></div>
                    </div>
                </div>
            </div>
            
            <hr>
            
            <!-- Academic Information -->
            <div>
                <h4 style="font-size:13px;font-weight:600;color:#6b7280;text-transform:uppercase;">
                    <i class="fas fa-graduation-cap" style="color:#2563eb;"></i> Academic Information
                </h4>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;">
                    <div>
                        <div style="font-size:12px;color:#6b7280;">Program</div>
                        <div style="font-weight:600;color:#111827;"><?= htmlspecialchars($app['program'] ?? $app['department_name'] ?? 'N/A') ?></div>
                    </div>
                    <div>
                        <div style="font-size:12px;color:#6b7280;">Previous Degree</div>
                        <div style="font-weight:600;color:#111827;"><?= htmlspecialchars($app['previous_degree'] ?? 'N/A') ?></div>
                    </div>
                    <div>
                        <div style="font-size:12px;color:#6b7280;">Obtained Marks</div>
                        <div style="font-weight:600;color:#111827;"><?= htmlspecialchars($app['obtained_marks'] ?? 'N/A') ?></div>
                    </div>
                    <div>
                        <div style="font-size:12px;color:#6b7280;">Total Marks</div>
                        <div style="font-weight:600;color:#111827;"><?= htmlspecialchars($app['total_marks'] ?? 'N/A') ?></div>
                    </div>
                    <div>
                        <div style="font-size:12px;color:#6b7280;">Percentage</div>
                        <div style="font-weight:600;color:#111827;"><?= isset($app['percentage']) ? round($app['percentage'], 2) . '%' : 'N/A' ?></div>
                    </div>
                    <div>
                        <div style="font-size:12px;color:#6b7280;">Applied Date</div>
                        <div style="font-weight:600;color:#111827;"><?= isset($app['submitted_at']) ? date('d M Y', strtotime($app['submitted_at'])) : 'N/A' ?></div>
                    </div>
                </div>
            </div>
            
            <?php if($fee_exists && $fee_record): ?>
            <hr>
            <div style="background:#f0fdf4;padding:12px;border-radius:8px;">
                <h4 style="font-size:13px;font-weight:600;color:#065f46;margin:0 0 8px 0;">
                    <i class="fas fa-money-bill-wave"></i> Fee Information
                </h4>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:5px;font-size:13px;">
                    <div><span style="color:#6b7280;">Challan No:</span></div>
                    <div><strong><?= htmlspecialchars($fee_record['fee_challan_no']) ?></strong></div>
                    <div><span style="color:#6b7280;">Amount:</span></div>
                    <div><strong>Rs <?= number_format($fee_record['fee_amount'], 0) ?></strong></div>
                    <div><span style="color:#6b7280;">Status:</span></div>
                    <div>
                        <span class="status-badge <?= $fee_record['status'] ?>">
                            <?= ucfirst($fee_record['status']) ?>
                        </span>
                    </div>
                    <?php if($fee_record['due_date']): ?>
                    <div><span style="color:#6b7280;">Due Date:</span></div>
                    <div><?= date('d M Y', strtotime($fee_record['due_date'])) ?></div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- RIGHT COLUMN: Review Decision -->
    <div class="card">
        <div class="card-header">
            <div>
                <h3><i class="fas fa-gavel" style="color:#2563eb;"></i> Review Decision</h3>
                <p>Select approval decision for student application</p>
            </div>
        </div>
        <div class="card-content">
            <form method="POST" id="reviewForm">
                
                <!-- ============================================= -->
                <!-- ADMISSION DETAILS SECTION -->
                <!-- ============================================= -->
                <div style="background:#f0f9ff;border:1px solid #bae6fd;border-radius:8px;padding:16px;margin-bottom:16px;">
                    <h4 style="margin:0 0 12px 0;font-size:14px;color:#0369a1;">
                        <i class="fas fa-calendar-alt"></i> Admission Details
                    </h4>
                    
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;">
                        <!-- Session -->
                        <div>
                            <label style="font-size:12px;font-weight:600;color:#374151;display:block;margin-bottom:3px;">
                                Session <span style="color:#ef4444;">*</span>
                            </label>
                            <select name="session_id" id="session_id" style="width:100%;padding:6px 10px;border:1px solid #d1d5db;border-radius:4px;font-size:13px;">
                                <option value="">Select Session</option>
                                <?php foreach ($sessions as $session): ?>
                                    <option value="<?= $session['id'] ?>" <?= ($admission_details && $admission_details['session_id'] == $session['id']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($session['session_name'] . ' (' . $session['session_code'] . ')') ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <!-- Section -->
                        <div>
                            <label style="font-size:12px;font-weight:600;color:#374151;display:block;margin-bottom:3px;">
                                Section <span style="color:#ef4444;">*</span>
                            </label>
                            <select name="section_id" id="section_id" style="width:100%;padding:6px 10px;border:1px solid #d1d5db;border-radius:4px;font-size:13px;">
                                <option value="">Select Section</option>
                                <?php foreach ($sections as $section): ?>
                                    <option value="<?= $section['section_id'] ?>" <?= ($admission_details && $admission_details['section_id'] == $section['section_id']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($section['section_name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <!-- Semester -->
                        <div>
                            <label style="font-size:12px;font-weight:600;color:#374151;display:block;margin-bottom:3px;">
                                Semester <span style="color:#ef4444;">*</span>
                            </label>
                            <select name="semester_id" id="semester_id" style="width:100%;padding:6px 10px;border:1px solid #d1d5db;border-radius:4px;font-size:13px;">
                                <option value="">Select Semester</option>
                                <?php 
                                $selected_dept_id = $app['department_id'] ?? $app['program_id'] ?? 0;
                                
                                if(!empty($selected_dept_id)) {
                                    try {
                                        $sem_stmt = $pdo->prepare("SELECT semester_id, semester_name, semester_number FROM semesters WHERE department_id = ? ORDER BY semester_number ASC");
                                        $sem_stmt->execute([$selected_dept_id]);
                                        $filtered_semesters = $sem_stmt->fetchAll();
                                        
                                        foreach ($filtered_semesters as $semester): 
                                            $selected_sem = ($admission_details && $admission_details['semester_id'] == $semester['semester_id']) ? 'selected' : '';
                                ?>
                                            <option value="<?= $semester['semester_id'] ?>" <?= $selected_sem ?>>
                                                <?= htmlspecialchars($semester['semester_name']) ?>
                                            </option>
                                <?php 
                                        endforeach;
                                    } catch (PDOException $e) {
                                        echo "<option value=''>ERROR: " . htmlspecialchars($e->getMessage()) . "</option>";
                                    }
                                } else {
                                    echo "<option value=''>No Department found</option>";
                                }
                                ?>
                            </select>
                        </div>
                        
                        <!-- Fee Structure (Multi-Select) -->
                        <div style="grid-column: span 2;">
                            <label style="font-size:12px;font-weight:600;color:#374151;display:block;margin-bottom:3px;">
                                Fee Structures <span style="color:#ef4444;">*</span>
                            </label>
                            <select name="fee_structure_ids[]" id="fee_structure_ids" multiple style="width:100%;min-height:100px;padding:6px 10px;border:1px solid #d1d5db;border-radius:4px;font-size:13px;">
                                <?php 
                                $selected_fee_ids = [];
                                if ($admission_details && !empty($admission_details['selected_fee_ids'])) {
                                    $selected_fee_ids = array_map('trim', explode(',', $admission_details['selected_fee_ids']));
                                }
                                if (!empty($fee_structures)) {
                                    foreach ($fee_structures as $fs): 
                                        $fs_id = (string)$fs['fee_structure_id']; 
                                        $sel_fs = in_array($fs_id, $selected_fee_ids) ? 'selected' : '';
                                        $display_text = htmlspecialchars($fs['fee_type'] . ' (' . ($fs['department_name'] ?? 'N/A') . ') - Rs ' . number_format($fs['amount'] ?? 0, 0));
                                ?>
                                        <option value="<?= $fs_id ?>" <?= $sel_fs ?>>
                                            <?= $display_text ?>
                                        </option>
                                <?php 
                                    endforeach; 
                                } else {
                                    echo "<option value=''>No fee structures found</option>";
                                }
                                ?>
                            </select>
                            <div style="font-size:11px;color:#6b7280;margin-top:2px;">
                                <i class="fas fa-info-circle"></i> Hold Ctrl/Cmd to select multiple fee structures
                            </div>
                        </div>

                        <!-- Scholarship Assignment -->
                        <div>
                            <label style="font-size:12px;font-weight:600;color:#374151;display:block;margin-bottom:3px;">
                                Award Scholarship
                            </label>
                            <select name="scholarship_id" id="scholarship_id" style="width:100%;padding:6px 10px;border:1px solid #d1d5db;border-radius:4px;font-size:13px;">
                                <option value="">No Scholarship</option>
                                <?php
                                try {
                                    $scholarships = $pdo->query("SELECT * FROM admission_scholarships WHERE LOWER(status) = 'active' ORDER BY scholarship_name")->fetchAll();
                                    foreach ($scholarships as $sch):
                                        $sel = ($admission_details && ($admission_details['scholarship_id'] ?? 0) == $sch['scholarship_id']) ? 'selected' : '';
                                ?>
                                    <option value="<?= $sch['scholarship_id'] ?>" <?= $sel ?>>
                                        <?= htmlspecialchars($sch['scholarship_name']) ?> (<?= number_format($sch['percentage'] ?? 0, 0) ?>%)
                                    </option>
                                <?php endforeach;
                                } catch (Exception $e) { echo "<option value=''>Error loading scholarships</option>"; } ?>
                            </select>
                            <div style="font-size:11px;color:#6b7280;margin-top:2px;">
                                <i class="fas fa-award"></i> Optional - award a scholarship to this student
                            </div>
                        </div>
                        
                        <!-- Start Date -->
                        <div>
                            <label style="font-size:12px;font-weight:600;color:#374151;display:block;margin-bottom:3px;">
                                Start Date <span style="color:#ef4444;">*</span>
                            </label>
                            <input type="date" name="start_date" value="<?= $admission_details['start_date'] ?? date('Y-m-d') ?>" 
                                   style="width:100%;padding:6px 10px;border:1px solid #d1d5db;border-radius:4px;font-size:13px;">
                        </div>
                        
                        <!-- End Date -->
                        <div>
                            <label style="font-size:12px;font-weight:600;color:#374151;display:block;margin-bottom:3px;">
                                End Date <span style="color:#ef4444;">*</span>
                            </label>
                            <input type="date" name="end_date" value="<?= $admission_details['end_date'] ?? date('Y-m-d', strtotime('+4 months')) ?>" 
                                   style="width:100%;padding:6px 10px;border:1px solid #d1d5db;border-radius:4px;font-size:13px;">
                        </div>
                        
                        <!-- Admission Date -->
                        <div style="grid-column: span 2;">
                            <label style="font-size:12px;font-weight:600;color:#374151;display:block;margin-bottom:3px;">
                                Admission Date <span style="color:#ef4444;">*</span>
                            </label>
                            <input type="date" name="admission_date" value="<?= $admission_details['admission_date'] ?? date('Y-m-d') ?>" 
                                   style="width:100%;padding:6px 10px;border:1px solid #d1d5db;border-radius:4px;font-size:13px;">
                        </div>
                    </div>
                    
                    <!-- Save Admission Details Button -->
                    <div style="margin-top:12px;padding-top:12px;border-top:1px solid #bae6fd;">
                        <button type="submit" name="action" value="save_admission_details" class="btn btn-primary" style="width:100%;padding:8px;background:#0284c7;color:#fff;border:none;border-radius:4px;font-size:13px;font-weight:500;cursor:pointer;">
                            <i class="fas fa-save"></i> Save Admission Details
                        </button>
                        <div style="font-size:11px;color:#0369a1;margin-top:4px;text-align:center;">
                            <i class="fas fa-info-circle"></i> Save session, section, semester and fee structure
                        </div>
                    </div>
                </div>

                <!-- OPTION 1: Approve with Fee -->
                <div style="background:#f0fdf4;border:1px solid #bbf7d0;border-radius:8px;padding:16px;margin-bottom:16px;">
                    <label style="display:flex;align-items:center;gap:10px;cursor:pointer;">
                        <input type="radio" name="action" value="approve_with_fee" required <?= (!$fee_exists) ? 'checked' : '' ?>>
                        <div>
                            <div style="font-weight:600;color:#065f46;">✅ Approve with Registration Fee</div>
                            <div style="font-size:13px;color:#047857;">
                                <i class="fas fa-money-bill-wave"></i> Fee Amount: <strong id="fee_display">Rs 1,000</strong>
                                <br><em>Student will be created after fee payment confirmation</em>
                            </div>
                        </div>
                    </label>
                </div>

                <!-- OPTION 2: Confirm Payment & Create Student -->
                <div style="background:#eff6ff;border:1px solid #bfdbfe;border-radius:8px;padding:16px;margin-bottom:16px;">
                    <label style="display:flex;align-items:center;gap:10px;cursor:pointer;">
                        <input type="radio" name="action" value="confirm_payment" <?= ($fee_exists && $fee_record['status'] === 'pending') ? 'checked' : '' ?>>
                        <div>
                            <div style="font-weight:600;color:#1e40af;">💳 Confirm Payment & Create Student</div>
                            <div style="font-size:13px;color:#1e40af;">
                                <i class="fas fa-check-circle"></i> Mark fee as paid and create student account
                                <br><em>Only use after receiving payment</em>
                            </div>
                        </div>
                    </label>
                    
                    <!-- Payment Details -->
                    <div id="payment_details" style="display:<?= ($fee_exists && $fee_record['status'] === 'pending') ? 'block' : 'none' ?>;margin-top:12px;padding-top:12px;border-top:1px solid #bfdbfe;">
                        <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;">
                            <div>
                                <label style="font-size:12px;font-weight:600;color:#374151;">Payment Method *</label>
                                <select name="payment_method" style="width:100%;padding:8px;border:1px solid #d1d5db;border-radius:6px;font-size:13px;">
                                    <option value="Cash">Cash</option>
                                    <option value="Bank Transfer">Bank Transfer</option>
                                    <option value="Online">Online</option>
                                    <option value="Cheque">Cheque</option>
                                    <option value="Other">Other</option>
                                </select>
                            </div>
                            <div>
                                <label style="font-size:12px;font-weight:600;color:#374151;">Reference/Transaction ID</label>
                                <input type="text" name="payment_reference" placeholder="e.g., TRX-12345" style="width:100%;padding:8px;border:1px solid #d1d5db;border-radius:6px;font-size:13px;">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- OPTION 3: Reject Application -->
                <div style="background:#fff1f2;border:1px solid #fecaca;border-radius:8px;padding:16px;margin-bottom:16px;">
                    <label style="display:flex;align-items:center;gap:10px;cursor:pointer;">
                        <input type="radio" name="action" value="reject">
                        <div>
                            <div style="font-weight:600;color:#991b1b;">❌ Reject Application</div>
                            <div style="font-size:13px;color:#991b1b;">
                                <i class="fas fa-times-circle"></i> Application will be rejected
                            </div>
                        </div>
                    </label>
                    
                    <!-- Rejection Reason -->
                    <div id="rejection_reason_div" style="display:none;margin-top:12px;padding-top:12px;border-top:1px solid #fecaca;">
                        <label style="font-size:12px;font-weight:600;color:#374151;display:block;margin-bottom:4px;">
                            Rejection Reason <span style="color:#ef4444;">*</span>
                        </label>
                        <textarea name="rejection_reason" rows="2" placeholder="Enter reason for rejection..." 
                                  style="width:100%;padding:8px 12px;border:1px solid #d1d5db;border-radius:6px;font-size:13px;resize:vertical;"></textarea>
                    </div>
                </div>

                <!-- OPTION 4: Keep Under Review -->
                <div style="background:#fffbeb;border:1px solid #fcd34d;border-radius:8px;padding:16px;margin-bottom:16px;">
                    <label style="display:flex;align-items:center;gap:10px;cursor:pointer;">
                        <input type="radio" name="action" value="under_review">
                        <div>
                            <div style="font-weight:600;color:#92400e;">⏳ Keep Under Review</div>
                            <div style="font-size:13px;color:#92400e;">
                                <i class="fas fa-clock"></i> Application stays in review queue
                            </div>
                        </div>
                    </label>
                </div>

                <!-- SUBMIT BUTTON -->
                <div style="margin-top:20px;padding-top:16px;border-top:1px solid #e5e7eb;display:flex;gap:10px;flex-wrap:wrap;">
                    <a href="view.php?id=<?= $app['application_id'] ?>" style="padding:10px 20px;border:1px solid #d1d5db;border-radius:8px;text-decoration:none;color:#4b5563;font-size:14px;">
                        <i class="fas fa-times"></i> Cancel
                    </a>
                    <button type="submit" class="btn btn-primary" style="padding:10px 30px;background:#2563eb;color:#fff;border:none;border-radius:8px;font-size:14px;font-weight:500;cursor:pointer;">
                        <i class="fas fa-check"></i> Submit Review
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- JavaScript for Dynamic UI -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Update fee display when fee structure changes
    const feeStructureSelect = document.getElementById('fee_structure_ids');
    const feeDisplay = document.getElementById('fee_display');
    
    if (feeStructureSelect && feeDisplay) {
        feeStructureSelect.addEventListener('change', function() {
            let total = 0;
            for (let opt of this.selectedOptions) {
                const text = opt.text;
                const match = text.match(/Rs\s+([\d,]+)/);
                if (match) {
                    total += parseInt(match[1].replace(/,/g, ''));
                }
            }
            feeDisplay.textContent = total > 0 ? 'Rs ' + total.toLocaleString() : 'Rs 0';
        });
    }
    
    // Toggle rejection reason and payment details
    document.querySelectorAll('input[name="action"]').forEach(function(radio) {
        radio.addEventListener('change', function() {
            const rejectionDiv = document.getElementById('rejection_reason_div');
            const paymentDetails = document.getElementById('payment_details');
            
            if (rejectionDiv) rejectionDiv.style.display = 'none';
            if (paymentDetails) paymentDetails.style.display = 'none';
            
            if (this.value === 'reject') {
                if (rejectionDiv) rejectionDiv.style.display = 'block';
            } else if (this.value === 'confirm_payment') {
                if (paymentDetails) paymentDetails.style.display = 'block';
            }
        });
    });
    
    // Form validation
    document.getElementById('reviewForm').addEventListener('submit', function(e) {
        const action = document.querySelector('input[name="action"]:checked');
        
        if (!action) {
            e.preventDefault();
            alert('Please select an action.');
            return false;
        }
        
        if (action.value === 'approve_with_fee' || action.value === 'save_admission_details') {
            const session = document.getElementById('session_id');
            const section = document.getElementById('section_id');
            const semester = document.getElementById('semester_id');
            const feeStructure = document.getElementById('fee_structure_ids');
            
            if (!session || !session.value) {
                e.preventDefault();
                alert('Please select a session.');
                if (session) session.focus();
                return false;
            }
            if (!section || !section.value) {
                e.preventDefault();
                alert('Please select a section.');
                if (section) section.focus();
                return false;
            }
            if (!semester || !semester.value) {
                e.preventDefault();
                alert('Please select a semester.');
                if (semester) semester.focus();
                return false;
            }
            if (!feeStructure || !feeStructure.selectedOptions || feeStructure.selectedOptions.length === 0) {
                e.preventDefault();
                alert('Please select at least one fee structure.');
                if (feeStructure) feeStructure.focus();
                return false;
            }
        }
        
        if (action.value === 'reject') {
            const reason = document.querySelector('textarea[name="rejection_reason"]');
            if (!reason || !reason.value.trim()) {
                e.preventDefault();
                alert('Please enter a rejection reason.');
                if (reason) reason.focus();
                return false;
            }
        }
        
        if (action.value === 'confirm_payment') {
            const paymentMethod = document.querySelector('select[name="payment_method"]');
            if (!paymentMethod || !paymentMethod.value) {
                e.preventDefault();
                alert('Please select a payment method.');
                if (paymentMethod) paymentMethod.focus();
                return false;
            }
        }
        
        // Confirm before submitting
        let confirmMsg = 'Are you sure you want to proceed?';
        if (action.value === 'save_admission_details') {
            confirmMsg = 'This will save the admission details (session, section, semester, fee structure).';
        } else if (action.value === 'approve_with_fee') {
            confirmMsg = 'This will generate a fee challan for the student.\n\nStudent will be created after payment confirmation.';
        } else if (action.value === 'confirm_payment') {
            confirmMsg = 'This will mark the fee as PAID and create the student account.\n\nA student record will be created with default credentials.';
        } else if (action.value === 'reject') {
            confirmMsg = 'This will REJECT the application.\n\nStudent will NOT be created.';
        }
        return confirm(confirmMsg);
    });
});
</script>

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