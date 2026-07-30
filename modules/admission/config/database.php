<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Fix BASE_URL - remove trailing slash
define('BASE_URL', '/uni-mis-project/modules/admission/');
define('DB_HOST', 'localhost');
define('DB_NAME', 'university_mis');
define('DB_USER', 'root');
define('DB_PASS', '');

try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
} catch (PDOException $e) {
    die("Database Connection Failed: " . $e->getMessage());
}

// =============================================
// AUTHENTICATION FUNCTIONS
// =============================================

function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: /uni-mis-project/');
        exit();
    }
}

// =============================================
// HELPER FUNCTIONS
// =============================================

function sanitize($input) {
    return htmlspecialchars(strip_tags(trim($input)));
}

function generateApplicationNo() {
    $prefix = 'APP';
    $year = date('Y');
    $random = str_pad(rand(1, 99999), 5, '0', STR_PAD_LEFT);
    return $prefix . '-' . $year . '-' . $random;
}

// =============================================
// GENERATE STUDENT ID
// Format: UNI-YYYY-XXXXX
// Example: UNI-2024-00123
// =============================================
function generateStudentId() {
    global $pdo;
    
    // Get last student ID to increment
    $stmt = $pdo->query("SELECT student_id FROM admission_students ORDER BY id DESC LIMIT 1");
    $last = $stmt->fetch();
    
    if ($last && !empty($last['student_id'])) {
        // Extract the number from last ID (e.g., UNI-2024-00123 -> 00123)
        $parts = explode('-', $last['student_id']);
        $last_num = intval(end($parts));
        $new_num = $last_num + 1;
    } else {
        $new_num = 1;
    }
    
    $year = date('Y');
    $uni_code = 'UNI';  // University code
    
    // Format: UNI-2024-00123 (5 digits with leading zeros)
    $student_id = $uni_code . '-' . $year . '-' . str_pad($new_num, 5, '0', STR_PAD_LEFT);
    
    return $student_id;
}

// =============================================
// FLASH MESSAGES
// =============================================

function setFlash($type, $message) {
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function getFlash() {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

// =============================================
// STATUS BADGE
// =============================================

function getStatusBadge($status) {
    $map = [
        'Submitted' => 'warning',
        'Under Review' => 'info',
        'Approved' => 'success',
        'Rejected' => 'danger',
        'Admitted' => 'primary',
        'Cancelled' => 'secondary',
        'pending' => 'warning',
        'reviewed' => 'info',
        'active' => 'success',
        'inactive' => 'secondary'
    ];
    return $map[$status] ?? 'secondary';
}

// =============================================
// CURRENCY FORMATTING
// =============================================

function formatCurrency($amount) {
    return 'PKR ' . number_format($amount, 0);
}

// =============================================
// SCHOLARSHIP CALCULATION
// =============================================

function calculateScholarship($percentage, $fee_amount = 0) {
    // Define scholarship slabs
    $slabs = [
        ['min' => 90, 'max' => 100, 'percentage' => 100, 'label' => 'Full Scholarship'],
        ['min' => 80, 'max' => 89.99, 'percentage' => 75, 'label' => '75% Scholarship'],
        ['min' => 70, 'max' => 79.99, 'percentage' => 50, 'label' => '50% Scholarship'],
        ['min' => 60, 'max' => 69.99, 'percentage' => 25, 'label' => '25% Scholarship'],
        ['min' => 0, 'max' => 59.99, 'percentage' => 0, 'label' => 'No Scholarship']
    ];
    
    foreach ($slabs as $slab) {
        if ($percentage >= $slab['min'] && $percentage <= $slab['max']) {
            $scholarship_percentage = $slab['percentage'];
            $scholarship_label = $slab['label'];
            $scholarship_amount = ($fee_amount * $scholarship_percentage) / 100;
            
            return [
                'percentage' => $scholarship_percentage,
                'label' => $scholarship_label,
                'amount' => $scholarship_amount,
                'fee_after_scholarship' => $fee_amount - $scholarship_amount
            ];
        }
    }
    
    return [
        'percentage' => 0,
        'label' => 'No Scholarship',
        'amount' => 0,
        'fee_after_scholarship' => $fee_amount
    ];
}

function getScholarshipBadge($percentage) {
    if ($percentage >= 90) return 'bg-success';
    if ($percentage >= 80) return 'bg-primary';
    if ($percentage >= 70) return 'bg-info';
    if ($percentage >= 60) return 'bg-warning';
    return 'bg-secondary';
}
?>