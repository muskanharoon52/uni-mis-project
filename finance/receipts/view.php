<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit();
}
// Check if user is Finance Officer (role_id = 3)
if ($_SESSION['role_id'] != 3) {
    header('Location: ../auth/login.php?error=Access denied. Finance Officer only.');
    exit();
}
// ... baaki code
include $_SERVER['DOCUMENT_ROOT'] . '/MIS/finance/includes/header.php';
include $_SERVER['DOCUMENT_ROOT'] . '/MIS/config/db_connect.php';

if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: index.php?error=Invalid receipt ID");
    exit();
}

$receipt_id = mysqli_real_escape_string($conn, $_GET['id']);

$sql = "SELECT 
        r.receipt_id,
        r.receipt_no,
        r.issued_at,
        p.payment_id,
        p.amount_paid,
        p.payment_method,
        p.transaction_ref,
        p.payment_date,
        s.full_name,
        s.roll_no,
        s.father_name,
        s.email,
        s.contact_no,
        d.department_name,
        sm.semester_name,
        ses.session_name,
        sf.total_amount,
        sf.paid_amount,
        sf.remaining_amount,
        u.full_name AS issued_by_name
        FROM receipts r
        JOIN payments p ON p.payment_id = r.payment_id
        JOIN student_fee sf ON sf.student_fee_id = p.student_fee_id
        JOIN students s ON s.student_id = p.student_id
        JOIN departments d ON d.department_id = s.program_id
        JOIN semesters sm ON sm.semester_id = sf.semester_id
        JOIN sessions ses ON ses.session_id = sf.session_id
        LEFT JOIN users u ON u.user_id = r.issued_by
        WHERE r.receipt_id = '$receipt_id'";
$result = mysqli_query($conn, $sql);

if (mysqli_num_rows($result) == 0) {
    header("Location: index.php?error=Receipt not found");
    exit();
}

$receipt = mysqli_fetch_assoc($result);

// Check if PDF download requested
if (isset($_GET['download']) && $_GET['download'] == 'pdf') {
    // We'll use HTML to PDF conversion via browser print
    // For now, redirect to print
    echo "<script>window.print();</script>";
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Receipt #<?php echo htmlspecialchars($receipt['receipt_no']); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @media print {
            .no-print { display: none !important; }
            .card { border: 1px solid #000 !important; }
            .card-header { background-color: #f8f9fa !important; color: #000 !important; }
        }
        .receipt-border {
            border: 2px solid #198754;
            border-radius: 10px;
            padding: 20px;
        }
        .btn-pdf {
            background: #dc3545;
            color: #fff;
        }
        .btn-pdf:hover {
            background: #c82333;
            color: #fff;
        }
    </style>
</head>
<body>

<div class="container mt-4">
    <div class="row justify-content-center">
        <div class="col-md-8">
            
            <!-- ===== BUTTONS ===== -->
            <div class="no-print mb-3 text-end">
                <a href="index.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Back
                </a>
                <button onclick="window.print()" class="btn btn-primary">
                    <i class="fas fa-print"></i> Print
                </button>
                <button onclick="downloadPDF()" class="btn btn-pdf">
                    <i class="fas fa-file-pdf"></i> Download PDF
                </button>
            </div>

            <!-- ===== RECEIPT ===== -->
            <div class="card shadow receipt-border" id="receiptContent">
                <div class="card-header bg-success text-white text-center">
                    <h3><i class="fas fa-university"></i> University MIS</h3>
                    <h5>Official Fee Receipt</h5>
                </div>
                <div class="card-body">
                    
                    <!-- Header -->
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <p><strong>Receipt No:</strong> <span class="badge bg-primary"><?php echo htmlspecialchars($receipt['receipt_no']); ?></span></p>
                            <p><strong>Date:</strong> <?php echo date('d-M-Y h:i A', strtotime($receipt['issued_at'])); ?></p>
                            <p><strong>Payment Date:</strong> <?php echo date('d-M-Y h:i A', strtotime($receipt['payment_date'])); ?></p>
                        </div>
                        <div class="col-md-6 text-end">
                            <p><strong>Payment Method:</strong> <?php echo htmlspecialchars($receipt['payment_method']); ?></p>
                            <p><strong>Transaction Ref:</strong> <?php echo htmlspecialchars($receipt['transaction_ref'] ?? 'N/A'); ?></p>
                            <p><strong>Issued By:</strong> <?php echo htmlspecialchars($receipt['issued_by_name'] ?? 'System'); ?></p>
                        </div>
                    </div>

                    <hr>

                    <!-- Student Info -->
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <h6><i class="fas fa-user-graduate"></i> Student Information</h6>
                            <p>
                                <strong>Name:</strong> <?php echo htmlspecialchars($receipt['full_name']); ?><br>
                                <strong>Father Name:</strong> <?php echo htmlspecialchars($receipt['father_name'] ?? 'N/A'); ?><br>
                                <strong>Roll No:</strong> <?php echo htmlspecialchars($receipt['roll_no'] ?? 'N/A'); ?><br>
                                <strong>Program:</strong> <?php echo htmlspecialchars($receipt['department_name']); ?>
                            </p>
                        </div>
                        <div class="col-md-6">
                            <h6><i class="fas fa-info-circle"></i> Fee Details</h6>
                            <p>
                                <strong>Semester:</strong> <?php echo htmlspecialchars($receipt['semester_name']); ?><br>
                                <strong>Session:</strong> <?php echo htmlspecialchars($receipt['session_name']); ?><br>
                                <strong>Total Fee:</strong> PKR <?php echo number_format($receipt['total_amount'], 2); ?><br>
                                <strong>Paid Amount:</strong> PKR <?php echo number_format($receipt['paid_amount'], 2); ?>
                            </p>
                        </div>
                    </div>

                    <hr>

                    <!-- Payment Summary -->
                    <div class="row">
                        <div class="col-md-12">
                            <h6><i class="fas fa-money-bill-wave"></i> Payment Summary</h6>
                            <table class="table table-bordered">
                                <tr>
                                    <th style="width: 50%;">Amount Paid</th>
                                    <td><strong>PKR <?php echo number_format($receipt['amount_paid'], 2); ?></strong></td>
                                </tr>
                                <tr>
                                    <th>Remaining Amount</th>
                                    <td><strong>PKR <?php echo number_format($receipt['remaining_amount'], 2); ?></strong></td>
                                </tr>
                                <tr>
                                    <th>Payment Status</th>
                                    <td>
                                        <?php 
                                        if($receipt['remaining_amount'] == 0):
                                        ?>
                                            <span class="badge bg-success">Fully Paid</span>
                                        <?php elseif($receipt['paid_amount'] > 0): ?>
                                            <span class="badge bg-warning">Partially Paid</span>
                                        <?php else: ?>
                                            <span class="badge bg-danger">Unpaid</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </div>

                    <hr>

                    <!-- Footer -->
                    <div class="text-center text-muted">
                        <small>This is a system-generated receipt. Valid without signature.</small><br>
                        <small>Generated on: <?php echo date('d-M-Y h:i A'); ?></small>
                    </div>

                </div>
            </div>
        </div>
    </div>
</div>

<script>
function downloadPDF() {
    // Open print dialog with PDF option
    window.print();
}

// For better PDF download experience
document.addEventListener('DOMContentLoaded', function() {
    // Check if URL has download param
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get('download') === 'pdf') {
        setTimeout(function() {
            window.print();
        }, 500);
    }
});
</script>

<!-- ===== HTML2PDF Library for better PDF ===== -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js" integrity="sha512-GsLlZN/3F2ErC5ifS5QtgpiJtWd43JWSuIgh7mbzZ8zBps+dvLusV+eNQATqgA/HdeKFVgA5v3S/cIrLF7QnIg==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
<script>
function downloadPDF() {
    const element = document.getElementById('receiptContent');
    const opt = {
        margin:       1,
        filename:     'receipt-<?php echo htmlspecialchars($receipt['receipt_no']); ?>.pdf',
        image:        { type: 'jpeg', quality: 0.98 },
        html2canvas:  { scale: 2 },
        jsPDF:        { unit: 'in', format: 'a4', orientation: 'portrait' }
    };
    html2pdf().set(opt).from(element).save();
}
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>