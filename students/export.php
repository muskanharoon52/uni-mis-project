<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
requireSSO();

// Use global connection
global $conn;

// Get filter parameters
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$program = isset($_GET['program']) ? (int)$_GET['program'] : 0;
$status = isset($_GET['status']) ? $_GET['status'] : '';
$format = isset($_GET['format']) ? $_GET['format'] : '';

// Build query with correct column names based on your database
$query = "SELECT s.student_id, s.roll_no, 
          u.full_name, u.email, u.phone,
          s.father_name, s.batch_year, s.semester, 
          s.section, s.status, s.enrollment_date,
          p.program_name, p.program_code
          FROM students s
          LEFT JOIN users u ON s.user_id = u.user_id
          LEFT JOIN programs p ON s.program_id = p.program_id
          WHERE 1=1";

$params = [];
$types = "";

if (!empty($search)) {
    $query .= " AND (u.full_name LIKE ? OR s.roll_no LIKE ? OR u.email LIKE ? OR s.father_name LIKE ?)";
    $searchParam = "%$search%";
    $params[] = $searchParam;
    $params[] = $searchParam;
    $params[] = $searchParam;
    $params[] = $searchParam;
    $types .= "ssss";
}

if ($program > 0) {
    $query .= " AND s.program_id = ?";
    $params[] = $program;
    $types .= "i";
}

if (!empty($status)) {
    $query .= " AND s.status = ?";
    $params[] = $status;
    $types .= "s";
}

$query .= " ORDER BY s.student_id DESC";

// Execute query with error handling
$stmt = $conn->prepare($query);

if ($stmt === false) {
    die("Error in query: " . $conn->error);
}

if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$result = $stmt->get_result();
$students = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// If format is specified, export
if ($format === 'csv') {
    exportCSV($students);
} elseif ($format === 'excel') {
    exportExcel($students);
} elseif ($format === 'pdf') {
    exportPDF($students);
}

// ============================================
// SHOW EXPORT PAGE (NO FORMAT SPECIFIED)
// ============================================
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>

<style>
    .export-container {
        max-width: 900px;
        margin: 30px auto;
    }
    
    .export-option {
        background: white;
        padding: 30px 20px;
        border-radius: 10px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        margin-bottom: 20px;
        text-align: center;
        transition: all 0.3s ease;
        cursor: pointer;
        text-decoration: none;
        display: block;
        color: inherit;
        height: 100%;
    }
    
    .export-option:hover {
        transform: translateY(-5px);
        box-shadow: 0 5px 20px rgba(0,0,0,0.15);
        text-decoration: none;
        color: inherit;
    }
    
    .export-option .icon {
        font-size: 48px;
        margin-bottom: 15px;
        display: block;
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
        margin-top: 10px;
    }
    
    .export-option p {
        color: #7f8c8d;
        font-size: 14px;
        margin-bottom: 15px;
    }
    
    .export-option .badge {
        font-size: 12px;
        padding: 5px 15px;
    }
    
    .export-count {
        background: #f8f9fa;
        padding: 20px;
        border-radius: 8px;
        text-align: center;
        margin-bottom: 20px;
        border: 1px solid #dee2e6;
    }
    
    .export-count .number {
        font-size: 36px;
        font-weight: 700;
        color: #2c3e50;
    }
    
    .export-count .label {
        color: #6c757d;
        font-size: 14px;
    }
    
    .filter-info {
        background: #e8f4fd;
        padding: 10px 15px;
        border-radius: 8px;
        margin-bottom: 20px;
        border-left: 4px solid #3498db;
    }
    
    .filter-info small {
        color: #2c3e50;
    }
    
    .export-option .feature-tag {
        font-size: 11px;
        background: #f1c40f;
        color: #2c3e50;
        padding: 2px 10px;
        border-radius: 12px;
        display: inline-block;
        margin-top: 5px;
    }
</style>

<div class="main-content">
    <div class="container-fluid">
        <div class="export-container">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h4><i class="fas fa-file-export"></i> Export Students</h4>
                <a href="index.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Back
                </a>
            </div>
            
            <?php if (!empty($search) || $program > 0 || !empty($status)): ?>
                <div class="filter-info">
                    <i class="fas fa-filter"></i>
                    <small>
                        <strong>Active Filters:</strong>
                        <?php if (!empty($search)): ?>
                            <span class="badge bg-primary">Search: <?php echo htmlspecialchars($search); ?></span>
                        <?php endif; ?>
                        <?php if ($program > 0): ?>
                            <span class="badge bg-success">Program ID: <?php echo $program; ?></span>
                        <?php endif; ?>
                        <?php if (!empty($status)): ?>
                            <span class="badge bg-warning">Status: <?php echo htmlspecialchars($status); ?></span>
                        <?php endif; ?>
                    </small>
                </div>
            <?php endif; ?>
            
            <div class="export-count">
                <div class="number"><?php echo count($students); ?></div>
                <div class="label">students ready to export</div>
            </div>
            
            <div class="row">
                <div class="col-md-4">
                    <a href="?format=csv<?php 
                        $params = $_GET;
                        unset($params['format']);
                        if (!empty($params)) {
                            echo '&' . http_build_query($params);
                        }
                    ?>" class="export-option">
                        <span class="icon csv"><i class="fas fa-file-csv"></i></span>
                        <h5>CSV Format</h5>
                        <p>Comma separated values<br>Compatible with Excel, Google Sheets</p>
                        <span class="badge bg-success">Download</span>
                        <span class="feature-tag"><i class="fas fa-check"></i> Recommended</span>
                    </a>
                </div>
                
                <div class="col-md-4">
                    <a href="?format=excel<?php 
                        $params = $_GET;
                        unset($params['format']);
                        if (!empty($params)) {
                            echo '&' . http_build_query($params);
                        }
                    ?>" class="export-option">
                        <span class="icon excel"><i class="fas fa-file-excel"></i></span>
                        <h5>Excel Format</h5>
                        <p>Microsoft Excel format (.xls)<br>Compatible with all spreadsheet software</p>
                        <span class="badge bg-success">Download</span>
                        <span class="feature-tag"><i class="fas fa-check"></i> Styled</span>
                    </a>
                </div>
                
                <div class="col-md-4">
                    <a href="?format=pdf<?php 
                        $params = $_GET;
                        unset($params['format']);
                        if (!empty($params)) {
                            echo '&' . http_build_query($params);
                        }
                    ?>" class="export-option">
                        <span class="icon pdf"><i class="fas fa-file-pdf"></i></span>
                        <h5>PDF Format</h5>
                        <p>Portable Document Format (.pdf)<br>Best for printing and sharing</p>
                        <span class="badge bg-success">Download</span>
                        <span class="feature-tag"><i class="fas fa-check"></i> Print Ready</span>
                    </a>
                </div>
            </div>
            
            <div class="text-center mt-4">
                <small class="text-muted">
                    <i class="fas fa-info-circle"></i> 
                    PDF export uses HTML to PDF conversion. For best results, use Chrome or Firefox.
                </small>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>

<?php
// ============================================
// EXPORT FUNCTIONS
// ============================================

function exportCSV($students) {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="students_export_' . date('Y-m-d') . '.csv"');
    
    $output = fopen('php://output', 'w');
    
    // Add UTF-8 BOM for Excel compatibility
    fputs($output, "\xEF\xBB\xBF");
    
    // Headers
    fputcsv($output, [
        'Student ID', 'Roll No', 'Full Name', 'Email', 'Phone',
        "Father's Name", 'Program', 'Batch Year', 'Semester',
        'Section', 'Status', 'Enrollment Date'
    ]);
    
    // Data
    foreach ($students as $student) {
        $ordinal = ['1st', '2nd', '3rd', '4th', '5th', '6th', '7th', '8th'];
        $semester_text = isset($student['semester']) && $student['semester'] >= 1 && $student['semester'] <= 8 
            ? $ordinal[$student['semester'] - 1] . ' Semester' 
            : 'Semester ' . ($student['semester'] ?? 'N/A');
        
        fputcsv($output, [
            $student['student_id'] ?? 'N/A',
            $student['roll_no'] ?? 'N/A',
            $student['full_name'] ?? 'N/A',
            $student['email'] ?? 'N/A',
            $student['phone'] ?? 'N/A',
            $student['father_name'] ?? 'N/A',
            $student['program_name'] ?? 'N/A',
            $student['batch_year'] ?? 'N/A',
            $semester_text,
            $student['section'] ?? 'N/A',
            ucfirst($student['status'] ?? 'N/A'),
            $student['enrollment_date'] ?? 'N/A'
        ]);
    }
    
    fclose($output);
    exit;
}

function exportExcel($students) {
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment; filename="students_export_' . date('Y-m-d') . '.xls"');
    
    echo '<html>';
    echo '<head><meta charset="UTF-8"><title>Students Export</title>';
    echo '<style>
        body { font-family: Arial, sans-serif; }
        h2 { color: #2c3e50; border-bottom: 2px solid #3498db; padding-bottom: 10px; }
        table { border-collapse: collapse; width: 100%; font-size: 12px; }
        th { background-color: #3498db; color: white; padding: 8px; border: 1px solid #ddd; }
        td { padding: 6px 8px; border: 1px solid #ddd; }
        tr:nth-child(even) { background-color: #f9f9f9; }
        .footer { margin-top: 20px; color: #7f8c8d; font-size: 11px; }
        .status-active { color: #27ae60; font-weight: bold; }
        .status-confirmed { color: #3498db; font-weight: bold; }
        .status-pending { color: #f39c12; font-weight: bold; }
        .status-inactive { color: #95a5a6; font-weight: bold; }
        .status-graduated { color: #2c3e50; font-weight: bold; }
    </style>';
    echo '</head><body>';
    
    echo '<h2>📊 Students List - ' . date('d M Y') . '</h2>';
    echo '<p>Total Students: ' . count($students) . '</p>';
    echo '<table>';
    echo '<thead>';
    echo '<tr>';
    echo '<th>#</th>';
    echo '<th>Student ID</th>';
    echo '<th>Roll No</th>';
    echo '<th>Full Name</th>';
    echo '<th>Email</th>';
    echo '<th>Phone</th>';
    echo "<th>Father's Name</th>";
    echo '<th>Program</th>';
    echo '<th>Batch Year</th>';
    echo '<th>Semester</th>';
    echo '<th>Section</th>';
    echo '<th>Status</th>';
    echo '<th>Enrollment Date</th>';
    echo '</tr>';
    echo '</thead>';
    echo '<tbody>';
    
    $i = 1;
    foreach ($students as $student) {
        $ordinal = ['1st', '2nd', '3rd', '4th', '5th', '6th', '7th', '8th'];
        $semester_text = isset($student['semester']) && $student['semester'] >= 1 && $student['semester'] <= 8 
            ? $ordinal[$student['semester'] - 1] . ' Semester' 
            : 'Semester ' . ($student['semester'] ?? 'N/A');
        
        $status = $student['status'] ?? 'N/A';
        $status_class = 'status-' . strtolower($status);
        
        echo '<tr>';
        echo '<td>' . $i++ . '</td>';
        echo '<td>' . htmlspecialchars($student['student_id'] ?? 'N/A') . '</td>';
        echo '<td>' . htmlspecialchars($student['roll_no'] ?? 'N/A') . '</td>';
        echo '<td><strong>' . htmlspecialchars($student['full_name'] ?? 'N/A') . '</strong></td>';
        echo '<td>' . htmlspecialchars($student['email'] ?? 'N/A') . '</td>';
        echo '<td>' . htmlspecialchars($student['phone'] ?? 'N/A') . '</td>';
        echo '<td>' . htmlspecialchars($student['father_name'] ?? 'N/A') . '</td>';
        echo '<td>' . htmlspecialchars($student['program_name'] ?? 'N/A') . '</td>';
        echo '<td>' . htmlspecialchars($student['batch_year'] ?? 'N/A') . '</td>';
        echo '<td>' . htmlspecialchars($semester_text) . '</td>';
        echo '<td>' . htmlspecialchars($student['section'] ?? 'N/A') . '</td>';
        echo '<td class="' . $status_class . '">' . ucfirst($status) . '</td>';
        echo '<td>' . htmlspecialchars($student['enrollment_date'] ?? 'N/A') . '</td>';
        echo '</tr>';
    }
    
    echo '</tbody>';
    echo '</table>';
    echo '<div class="footer">';
    echo '<p><em>Generated on: ' . date('Y-m-d H:i:s') . ' | System: University MIS</em></p>';
    echo '</div>';
    echo '</body></html>';
    exit;
}

function exportPDF($students) {
    // ============================================
    // PDF EXPORT USING HTML TO PDF (No external library)
    // ============================================
    
    // If you have dompdf installed, use it
    // Otherwise, use this HTML-based PDF approach
    
    // Check if dompdf is available
    $dompdf_path = __DIR__ . '/../vendor/autoload.php';
    
    if (file_exists($dompdf_path)) {
        // Use dompdf for better PDF generation
        require_once $dompdf_path;
        
        $html = generatePDFHTML($students);
        
        $dompdf = new Dompdf\Dompdf();
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->render();
        $dompdf->stream("students_export_" . date('Y-m-d') . ".pdf", array("Attachment" => 1));
        exit;
    } else {
        // Fallback: Use HTML-based PDF (prints as PDF in browser)
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="students_export_' . date('Y-m-d') . '.pdf"');
        
        echo generatePDFHTML($students);
        exit;
    }
}

function generatePDFHTML($students) {
    $html = '<!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <title>Students Export</title>
        <style>
            * { margin: 0; padding: 0; box-sizing: border-box; }
            body { 
                font-family: "DejaVu Sans", Arial, sans-serif; 
                padding: 20px;
                background: white;
            }
            .header {
                text-align: center;
                padding: 20px 0;
                border-bottom: 3px solid #3498db;
                margin-bottom: 20px;
            }
            .header h1 {
                color: #2c3e50;
                font-size: 24px;
            }
            .header .subtitle {
                color: #7f8c8d;
                font-size: 14px;
            }
            .header .date {
                color: #95a5a6;
                font-size: 12px;
                margin-top: 5px;
            }
            .info-box {
                background: #ecf0f1;
                padding: 10px 15px;
                border-radius: 5px;
                margin-bottom: 15px;
                display: inline-block;
            }
            .info-box span {
                font-weight: bold;
                color: #2c3e50;
            }
            table {
                width: 100%;
                border-collapse: collapse;
                font-size: 11px;
                margin-top: 10px;
            }
            th {
                background: #3498db;
                color: white;
                padding: 6px 8px;
                text-align: left;
                border: 1px solid #2980b9;
            }
            td {
                padding: 5px 8px;
                border: 1px solid #ddd;
            }
            tr:nth-child(even) {
                background: #f9f9f9;
            }
            tr:hover {
                background: #f1f1f1;
            }
            .status-active { color: #27ae60; font-weight: bold; }
            .status-confirmed { color: #3498db; font-weight: bold; }
            .status-pending { color: #f39c12; font-weight: bold; }
            .status-inactive { color: #95a5a6; font-weight: bold; }
            .status-graduated { color: #2c3e50; font-weight: bold; }
            .footer {
                margin-top: 20px;
                text-align: center;
                color: #95a5a6;
                font-size: 10px;
                border-top: 1px solid #ddd;
                padding-top: 15px;
            }
            .watermark {
                position: fixed;
                top: 50%;
                left: 50%;
                transform: translate(-50%, -50%) rotate(-30deg);
                font-size: 72px;
                color: rgba(0,0,0,0.05);
                z-index: -1;
                pointer-events: none;
            }
            @page {
                margin: 15mm;
            }
            .page-break {
                page-break-after: always;
            }
        </style>
    </head>
    <body>
        <div class="watermark">UNIVERSITY MIS</div>
        
        <div class="header">
            <h1>📊 Students Report</h1>
            <div class="subtitle">University Management Information System</div>
            <div class="date">Generated: ' . date('l, d F Y h:i A') . '</div>
        </div>
        
        <div class="info-box">
            <span>' . count($students) . '</span> Students Found
        </div>';
    
    if (!empty($students)) {
        $html .= '<table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Student ID</th>
                    <th>Roll No</th>
                    <th>Full Name</th>
                    <th>Email</th>
                    <th>Phone</th>
                    <th>Father\'s Name</th>
                    <th>Program</th>
                    <th>Batch</th>
                    <th>Semester</th>
                    <th>Section</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>';
        
        $i = 1;
        $ordinal = ['1st', '2nd', '3rd', '4th', '5th', '6th', '7th', '8th'];
        
        foreach ($students as $student) {
            $semester_text = isset($student['semester']) && $student['semester'] >= 1 && $student['semester'] <= 8 
                ? $ordinal[$student['semester'] - 1] . ' Sem' 
                : 'Sem ' . ($student['semester'] ?? 'N/A');
            
            $status = strtolower($student['status'] ?? 'N/A');
            $status_class = 'status-' . $status;
            $status_display = ucfirst($status);
            
            $html .= '<tr>
                <td>' . $i++ . '</td>
                <td>' . htmlspecialchars($student['student_id'] ?? 'N/A') . '</td>
                <td>' . htmlspecialchars($student['roll_no'] ?? 'N/A') . '</td>
                <td><strong>' . htmlspecialchars($student['full_name'] ?? 'N/A') . '</strong></td>
                <td>' . htmlspecialchars($student['email'] ?? 'N/A') . '</td>
                <td>' . htmlspecialchars($student['phone'] ?? 'N/A') . '</td>
                <td>' . htmlspecialchars($student['father_name'] ?? 'N/A') . '</td>
                <td>' . htmlspecialchars($student['program_name'] ?? 'N/A') . '</td>
                <td>' . htmlspecialchars($student['batch_year'] ?? 'N/A') . '</td>
                <td>' . htmlspecialchars($semester_text) . '</td>
                <td>' . htmlspecialchars($student['section'] ?? 'N/A') . '</td>
                <td class="' . $status_class . '">' . $status_display . '</td>
            </tr>';
        }
        
        $html .= '</tbody></table>';
    } else {
        $html .= '<p style="text-align:center; padding:40px; color:#95a5a6; font-size:16px;">
            <i class="fas fa-info-circle"></i> No students found matching the criteria.
        </p>';
    }
    
    $html .= '<div class="footer">
        <p>This is a computer-generated document. No signature is required.</p>
        <p>© ' . date('Y') . ' University MIS - All Rights Reserved</p>
    </div>
    </body>
    </html>';
    
    return $html;
}
?>