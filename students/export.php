<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
requireLogin();

$conn = getConnection();

// Get filter parameters (optional)
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$department = isset($_GET['department']) ? (int)$_GET['department'] : 0;
$status = isset($_GET['status']) ? $_GET['status'] : '';
$format = isset($_GET['format']) ? $_GET['format'] : 'csv'; // csv or excel

// Build query
$query = "SELECT s.student_id, s.full_name, s.email, s.phone, 
          s.date_of_birth, s.gender, s.address,
          d.name as department_name, 
          c.name as course_name,
          s.semester, s.admission_date, s.status
          FROM students s
          LEFT JOIN departments d ON s.department_id = d.id
          LEFT JOIN courses c ON s.course_id = c.id
          WHERE 1=1";

$params = [];
$types = "";

if (!empty($search)) {
    $query .= " AND (s.full_name LIKE ? OR s.student_id LIKE ? OR s.email LIKE ?)";
    $searchParam = "%$search%";
    $params[] = $searchParam;
    $params[] = $searchParam;
    $params[] = $searchParam;
    $types .= "sss";
}

if ($department > 0) {
    $query .= " AND s.department_id = ?";
    $params[] = $department;
    $types .= "i";
}

if (!empty($status)) {
    $query .= " AND s.status = ?";
    $params[] = $status;
    $types .= "s";
}

$query .= " ORDER BY s.created_at DESC";

// Execute query
$stmt = $conn->prepare($query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();
$students = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Set headers for download
if ($format === 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="students_export_' . date('Y-m-d') . '.csv"');
    
    $output = fopen('php://output', 'w');
    
    // Add UTF-8 BOM for Excel compatibility
    fputs($output, "\xEF\xBB\xBF");
    
    // Headers
    fputcsv($output, [
        'Student ID', 'Full Name', 'Email', 'Phone', 'Date of Birth',
        'Gender', 'Address', 'Department', 'Course', 'Semester',
        'Admission Date', 'Status'
    ]);
    
    // Data
    foreach ($students as $student) {
        fputcsv($output, [
            $student['student_id'],
            $student['full_name'],
            $student['email'],
            $student['phone'] ?? '',
            $student['date_of_birth'],
            $student['gender'],
            $student['address'] ?? '',
            $student['department_name'] ?? '',
            $student['course_name'] ?? '',
            'Semester ' . $student['semester'],
            $student['admission_date'],
            $student['status']
        ]);
    }
    
    fclose($output);
    exit;
    
} elseif ($format === 'excel') {
    // Excel format (HTML table with .xls extension)
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment; filename="students_export_' . date('Y-m-d') . '.xls"');
    
    echo '<html>';
    echo '<head><meta charset="UTF-8"><title>Students Export</title></head>';
    echo '<body>';
    echo '<h2>Students List - ' . date('d M Y') . '</h2>';
    echo '<table border="1" cellpadding="5">';
    echo '<thead>';
    echo '<tr style="background-color: #3498db; color: white;">';
    echo '<th>Student ID</th>';
    echo '<th>Full Name</th>';
    echo '<th>Email</th>';
    echo '<th>Phone</th>';
    echo '<th>Date of Birth</th>';
    echo '<th>Gender</th>';
    echo '<th>Address</th>';
    echo '<th>Department</th>';
    echo '<th>Course</th>';
    echo '<th>Semester</th>';
    echo '<th>Admission Date</th>';
    echo '<th>Status</th>';
    echo '</tr>';
    echo '</thead>';
    echo '<tbody>';
    
    foreach ($students as $student) {
        echo '<tr>';
        echo '<td>' . htmlspecialchars($student['student_id']) . '</td>';
        echo '<td>' . htmlspecialchars($student['full_name']) . '</td>';
        echo '<td>' . htmlspecialchars($student['email']) . '</td>';
        echo '<td>' . htmlspecialchars($student['phone'] ?? '') . '</td>';
        echo '<td>' . htmlspecialchars($student['date_of_birth']) . '</td>';
        echo '<td>' . htmlspecialchars($student['gender']) . '</td>';
        echo '<td>' . htmlspecialchars($student['address'] ?? '') . '</td>';
        echo '<td>' . htmlspecialchars($student['department_name'] ?? '') . '</td>';
        echo '<td>' . htmlspecialchars($student['course_name'] ?? '') . '</td>';
        echo '<td>Semester ' . htmlspecialchars($student['semester']) . '</td>';
        echo '<td>' . htmlspecialchars($student['admission_date']) . '</td>';
        echo '<td>' . htmlspecialchars($student['status']) . '</td>';
        echo '</tr>';
    }
    
    echo '</tbody>';
    echo '</table>';
    echo '<p><em>Generated on: ' . date('Y-m-d H:i:s') . '</em></p>';
    echo '</body></html>';
    exit;
}

// If no format specified, show export page
include __DIR__ . '/../includes/header.php';
$page_title = 'Export Students';
include __DIR__ . '/../includes/navbar.php';
?>

<style>
    .export-container {
        max-width: 800px;
        margin: 30px auto;
    }
    
    .export-option {
        background: white;
        padding: 30px;
        border-radius: 10px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        margin-bottom: 20px;
        text-align: center;
        transition: all 0.3s ease;
        cursor: pointer;
    }
    
    .export-option:hover {
        transform: translateY(-5px);
        box-shadow: 0 5px 20px rgba(0,0,0,0.15);
    }
    
    .export-option .icon {
        font-size: 48px;
        margin-bottom: 15px;
    }
    
    .export-option .icon.csv {
        color: #27ae60;
    }
    
    .export-option .icon.excel {
        color: #217346;
    }
    
    .export-option .icon.pdf {
        color: #e74c3c;
    }
    
    .export-option h5 {
        font-weight: 600;
        color: #2c3e50;
    }
    
    .export-option p {
        color: #7f8c8d;
        font-size: 14px;
    }
    
    .export-count {
        background: #f8f9fa;
        padding: 15px;
        border-radius: 8px;
        text-align: center;
        margin-bottom: 20px;
    }
    
    .export-count .number {
        font-size: 32px;
        font-weight: 700;
        color: #2c3e50;
    }
</style>

<div class="container-fluid">
    <div class="export-container">
        <h4 class="mb-4"><i class="fas fa-file-export"></i> Export Students</h4>
        
        <div class="export-count">
            <div class="number"><?php echo count($students); ?></div>
            <p class="text-muted">students ready to export</p>
        </div>
        
        <div class="row">
            <div class="col-md-4">
                <a href="?format=csv<?php echo !empty($_GET) ? '&' . http_build_query(array_filter($_GET, function($k) { return $k != 'format'; }, ARRAY_FILTER_USE_KEY)) : ''; ?>" 
                   class="text-decoration-none">
                    <div class="export-option">
                        <div class="icon csv">
                            <i class="fas fa-file-csv"></i>
                        </div>
                        <h5>CSV Format</h5>
                        <p>Comma separated values<br>Compatible with Excel, Google Sheets</p>
                    </div>
                </a>
            </div>
            
            <div class="col-md-4">
                <a href="?format=excel<?php echo !empty($_GET) ? '&' . http_build_query(array_filter($_GET, function($k) { return $k != 'format'; }, ARRAY_FILTER_USE_KEY)) : ''; ?>" 
                   class="text-decoration-none">
                    <div class="export-option">
                        <div class="icon excel">
                            <i class="fas fa-file-excel"></i>
                        </div>
                        <h5>Excel Format</h5>
                        <p>Microsoft Excel format<br>Compatible with all spreadsheet software</p>
                    </div>
                </a>
            </div>
            
            <div class="col-md-4">
                <a href="javascript:void(0)" onclick="alert('PDF export coming soon!')" class="text-decoration-none">
                    <div class="export-option">
                        <div class="icon pdf">
                            <i class="fas fa-file-pdf"></i>
                        </div>
                        <h5>PDF Format</h5>
                        <p>Portable Document Format<br>Best for printing and sharing</p>
                    </div>
                </a>
            </div>
        </div>
        
        <div class="text-center mt-3">
            <a href="index.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to Students
            </a>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>