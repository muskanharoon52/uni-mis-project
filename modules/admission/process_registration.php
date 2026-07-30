<?php
// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// =============================================
// DATABASE CONNECTION
// =============================================
// Try multiple possible paths
$db_paths = [
    '../../config/database.php',
    '../config/database.php',
    'config/database.php',
    __DIR__ . '/../../config/database.php',
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
    try {
        $host = 'localhost';
        $dbname = 'university_mis';
        $username = 'root';
        $password = '';
        
        $conn = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    } catch (PDOException $e) {
        die("Database connection failed: " . $e->getMessage());
    }
} elseif (!isset($conn) && isset($pdo)) {
    $conn = $pdo;
}

// Check if form was submitted
if ($_SERVER['REQUEST_METHOD'] != 'POST') {
    header('Location: register.php');
    exit();
}

// =============================================
// COLLECT AND SANITIZE FORM DATA
// =============================================
$full_name = trim($_POST['full_name'] ?? '');
$father_name = trim($_POST['father_name'] ?? '');
$cnic = trim($_POST['cnic'] ?? '');
$dob = $_POST['dob'] ?? '';
$gender = $_POST['gender'] ?? '';
$phone = trim($_POST['phone'] ?? '');
$email = trim($_POST['email'] ?? '');
$previous_degree = $_POST['previous_degree'] ?? '';
$program = $_POST['program'] ?? '';
$obtained_marks = floatval($_POST['obtained_marks'] ?? 0);
$total_marks = floatval($_POST['total_marks'] ?? 0);
$password = $_POST['password'] ?? '';
$confirm_password = $_POST['confirm_password'] ?? '';
$address = trim($_POST['address'] ?? '');

// =============================================
// VALIDATION
// =============================================
$errors = [];

if (empty($full_name)) $errors[] = "Full Name is required";
if (empty($father_name)) $errors[] = "Father's Name is required";
if (empty($cnic)) $errors[] = "CNIC is required";
if (empty($dob)) $errors[] = "Date of Birth is required";
if (empty($gender)) $errors[] = "Gender is required";
if (empty($phone)) $errors[] = "Phone Number is required";
if (empty($email)) $errors[] = "Email is required";
if (empty($previous_degree)) $errors[] = "Previous Degree is required";
if (empty($program)) $errors[] = "Program is required";
if (empty($password)) $errors[] = "Password is required";
if (empty($confirm_password)) $errors[] = "Confirm Password is required";

if (!empty($password) && !empty($confirm_password) && $password !== $confirm_password) {
    $errors[] = "Passwords do not match!";
}

if (!empty($password) && strlen($password) < 6) {
    $errors[] = "Password must be at least 6 characters long!";
}

if (!empty($cnic) && !preg_match('/^[0-9]{13}$/', $cnic)) {
    $errors[] = "Invalid CNIC format! Must be 13 digits.";
}

if (!empty($phone) && !preg_match('/^[0-9]{11}$/', $phone)) {
    $errors[] = "Invalid phone format! Must be 11 digits.";
}

if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = "Invalid email address!";
}

if ($obtained_marks > $total_marks) {
    $errors[] = "Obtained marks cannot be greater than total marks!";
}

// =============================================
// GET TABLE COLUMNS
// =============================================
try {
    $columns_query = "SHOW COLUMNS FROM admission_applications";
    $columns_stmt = $conn->query($columns_query);
    $columns = $columns_stmt->fetchAll(PDO::FETCH_COLUMN);
} catch (PDOException $e) {
    $_SESSION['error'] = "Error reading table structure: " . $e->getMessage();
    header("Location: register.php");
    exit();
}

// =============================================
// CHECK FOR DUPLICATE APPLICATIONS
// =============================================
if (empty($errors) && isset($conn)) {
    try {
        $cnic_column = null;
        if (in_array('cnic', $columns)) $cnic_column = 'cnic';
        elseif (in_array('cnic_or_bform', $columns)) $cnic_column = 'cnic_or_bform';
        
        $email_column = in_array('email', $columns) ? 'email' : (in_array('email_address', $columns) ? 'email_address' : null);
        
        if ($cnic_column && $email_column) {
            $check_query = "SELECT * FROM admission_applications WHERE $cnic_column = ? OR $email_column = ?";
            $stmt = $conn->prepare($check_query);
            $stmt->execute([$cnic, $email]);
            
            if ($stmt->rowCount() > 0) {
                $errors[] = "You have already applied with this CNIC or Email!";
            }
        }
    } catch (PDOException $e) {
        error_log("Duplicate check failed: " . $e->getMessage());
    }
}

if (!empty($errors)) {
    $_SESSION['error'] = implode("<br>", $errors);
    header("Location: register.php");
    exit();
}

// =============================================
// PROCESS REGISTRATION
// =============================================
try {
    // Hash the password
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    
    // Calculate percentage
    $percentage = ($total_marks > 0) ? ($obtained_marks / $total_marks) * 100 : 0;
    
    // Get current timestamp
    $now = date('Y-m-d H:i:s');
    
    // =============================================
    // GENERATE UNIQUE APPLICATION ID
    // =============================================
    $application_id = 'APP-' . date('Y') . '-' . str_pad(rand(1, 99999), 5, '0', STR_PAD_LEFT);
    
    if (in_array('application_id', $columns)) {
        $unique = false;
        $attempts = 0;
        while (!$unique && $attempts < 10) {
            $check_stmt = $conn->prepare("SELECT COUNT(*) FROM admission_applications WHERE application_id = ?");
            $check_stmt->execute([$application_id]);
            if ($check_stmt->fetchColumn() == 0) {
                $unique = true;
            } else {
                $application_id = 'APP-' . date('Y') . '-' . str_pad(rand(1, 99999), 5, '0', STR_PAD_LEFT);
                $attempts++;
            }
        }
    }
    
    // =============================================
    // BUILD INSERT DATA - SKIP FOREIGN KEY COLUMNS
    // =============================================
    // Columns to skip (foreign keys that cause errors)
    $skip_columns = ['session_id', 'applied_semester_id', 'reviewed_by'];
    
    $insert_data = [];
    
    // Application ID
    if (in_array('application_id', $columns) && !in_array('application_id', $skip_columns)) {
        $insert_data['application_id'] = $application_id;
    }
    
    // Name fields
    if (in_array('full_name', $columns) && !in_array('full_name', $skip_columns)) {
        $insert_data['full_name'] = $full_name;
    }
    
    if (in_array('father_name', $columns) && !in_array('father_name', $skip_columns)) {
        $insert_data['father_name'] = $father_name;
    } elseif (in_array('guardian_name', $columns) && !in_array('guardian_name', $skip_columns)) {
        $insert_data['guardian_name'] = $father_name;
    }
    
    // CNIC
    if (in_array('cnic', $columns) && !in_array('cnic', $skip_columns)) {
        $insert_data['cnic'] = $cnic;
    } elseif (in_array('cnic_or_bform', $columns) && !in_array('cnic_or_bform', $skip_columns)) {
        $insert_data['cnic_or_bform'] = $cnic;
    }
    
    // DOB
    if (in_array('dob', $columns) && !in_array('dob', $skip_columns)) {
        $insert_data['dob'] = $dob;
    }
    
    // Gender
    if (in_array('gender', $columns) && !in_array('gender', $skip_columns)) {
        $insert_data['gender'] = $gender;
    }
    
    // Phone
    if (in_array('phone', $columns) && !in_array('phone', $skip_columns)) {
        $insert_data['phone'] = $phone;
    } elseif (in_array('contact_no', $columns) && !in_array('contact_no', $skip_columns)) {
        $insert_data['contact_no'] = $phone;
    } elseif (in_array('mobile', $columns) && !in_array('mobile', $skip_columns)) {
        $insert_data['mobile'] = $phone;
    }
    
    // Email
    if (in_array('email', $columns) && !in_array('email', $skip_columns)) {
        $insert_data['email'] = $email;
    } elseif (in_array('email_address', $columns) && !in_array('email_address', $skip_columns)) {
        $insert_data['email_address'] = $email;
    }
    
    // Previous Degree
    if (in_array('previous_degree', $columns) && !in_array('previous_degree', $skip_columns)) {
        $insert_data['previous_degree'] = $previous_degree;
    }
    
    // Program - use text column instead of foreign key
    if (in_array('program', $columns) && !in_array('program', $skip_columns)) {
        $insert_data['program'] = $program;
    } elseif (in_array('department', $columns) && !in_array('department', $skip_columns)) {
        $insert_data['department'] = $program;
    } elseif (in_array('course', $columns) && !in_array('course', $skip_columns)) {
        $insert_data['course'] = $program;
    }
    
    // Marks
    if (in_array('obtained_marks', $columns) && !in_array('obtained_marks', $skip_columns)) {
        $insert_data['obtained_marks'] = $obtained_marks;
    }
    
    if (in_array('total_marks', $columns) && !in_array('total_marks', $skip_columns)) {
        $insert_data['total_marks'] = $total_marks;
    }
    
    if (in_array('percentage', $columns) && !in_array('percentage', $skip_columns)) {
        $insert_data['percentage'] = $percentage;
    }
    
    // Password
    if (in_array('password', $columns) && !in_array('password', $skip_columns)) {
        $insert_data['password'] = $hashed_password;
    }
    
    // Address
    if (in_array('address', $columns) && !in_array('address', $skip_columns)) {
        $insert_data['address'] = $address;
    } elseif (in_array('residential_address', $columns) && !in_array('residential_address', $skip_columns)) {
        $insert_data['residential_address'] = $address;
    } elseif (in_array('home_address', $columns) && !in_array('home_address', $skip_columns)) {
        $insert_data['home_address'] = $address;
    }
    
    // Status
    if (in_array('status', $columns) && !in_array('status', $skip_columns)) {
        $insert_data['status'] = 'pending';
    } elseif (in_array('application_status', $columns) && !in_array('application_status', $skip_columns)) {
        $insert_data['application_status'] = 'pending';
    }
    
    // Date columns
    if (in_array('submitted_at', $columns) && !in_array('submitted_at', $skip_columns)) {
        $insert_data['submitted_at'] = $now;
    }
    
    if (in_array('created_at', $columns) && !in_array('created_at', $skip_columns)) {
        $insert_data['created_at'] = $now;
    }
    
    if (in_array('applied_date', $columns) && !in_array('applied_date', $skip_columns)) {
        $insert_data['applied_date'] = $now;
    }
    
    // =============================================
    // CHECK IF WE HAVE DATA TO INSERT
    // =============================================
    if (empty($insert_data)) {
        throw new Exception("No matching columns found in the database table!");
    }
    
    // =============================================
    // BUILD AND EXECUTE INSERT QUERY
    // =============================================
    $fields = array_keys($insert_data);
    $placeholders = array_fill(0, count($fields), '?');
    
    $sql = "INSERT INTO admission_applications (" . implode(', ', $fields) . ") 
            VALUES (" . implode(', ', $placeholders) . ")";
    
    $stmt = $conn->prepare($sql);
    $result = $stmt->execute(array_values($insert_data));
    
    if ($result) {
        // Store in session for popup
        $_SESSION['application_id'] = $application_id;
        $_SESSION['full_name'] = $full_name;
        $_SESSION['email'] = $email;
        $_SESSION['success'] = "✅ Application Submitted Successfully!";
        
        header("Location: register.php");
        exit();
    } else {
        throw new Exception("Failed to insert application");
    }
    
} catch (PDOException $e) {
    $_SESSION['error'] = "Database Error: " . $e->getMessage();
    header("Location: register.php");
    exit();
} catch (Exception $e) {
    $_SESSION['error'] = "Error: " . $e->getMessage();
    header("Location: register.php");
    exit();
}
?>