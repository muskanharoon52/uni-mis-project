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

// Fetch receipt data
$sql = "SELECT 
        r.receipt_no,
        r.issued_at,
        p.amount_paid,
        p.payment_method,
        p.transaction_ref,
        p.payment_date,
        s.full_name,
        s.roll_no,
        s.father_name,
        sf.total_amount,
        sf.paid_amount,
        sf.remaining_amount,
        d.department_name,
        sm.semester_name,
        ses.session_name
        FROM receipts r
        JOIN payments p ON p.payment_id = r.payment_id
        JOIN student_fee sf ON sf.student_fee_id = p.student_fee_id
        JOIN students s ON s.student_id = p.student_id
        JOIN departments d ON d.department_id = s.program_id
        JOIN semesters sm ON sm.semester_id = sf.semester_id
        JOIN sessions ses ON ses.session_id = sf.session_id
        WHERE r.receipt_id = '$receipt_id'";
$result = mysqli_query($conn, $sql);

if (mysqli_num_rows($result) == 0) {
    header("Location: index.php?error=Receipt not found");
    exit();
}

$receipt = mysqli_fetch_assoc($result);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Print Receipt</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @media print {
            .no-print { display: none !important; }
        }
        .receipt-box {
            border: 2px solid #198754;
            border-radius: 10px;
            padding: 20px;
            margin-top: 20px;
            background: #fff;
        }
        .receipt-header {
            text-align: center;
            border-bottom: 2px solid #198754;
            padding-bottom: 15px;
            margin-bottom: 15px;
        }
        .receipt-footer {
            text-align: center;
            border-top: 2px solid #198754;
            padding-top: 15px;
            margin-top: 15px;
        }
        .table-details td {
            padding: 8px 12px;
        }
        .table-details tr:nth-child(even) {
            background-color: #f8f9fa;
        }
    </style>
</head>
<body>

<div class="container mt-4">
    <div class="row justify-content-center">
        <div class="col-md-8">
            
            <!-- ===== BUTTONS ===== -->
            <div class="no-print text-end mb-3">
                <button onclick="window.print()" class="btn btn-success">
                    <i class="fas fa-print"></i> Print
                </button>
                <a href="index.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Back
                </a>
            </div>

            <!-- ===== RECEIPT ===== -->
            <div class="receipt-box" id="receiptContent">
                <div class="receipt-header">
                    <h2><i class="fas fa-university"></i> University MIS</h2>
                    <h4>Official Fee Receipt</h4>
                </div>

                <!-- Receipt Details -->
                <table class="table table-bordered table-details">
                    <tr>
                        <th style="width: 35%;">Receipt No</th>
                        <td><strong><?php echo htmlspecialchars($receipt['receipt_no']); ?></strong></td>
                    </tr>
                    <tr>
                        <th>Issue Date</th>
                        <td><?php echo date('d-M-Y h:i A', strtotime($receipt['issued_at'])); ?></td>
                    </tr>
                    <tr>
                        <th>Payment Date</th>
                        <td><?php echo date('d-M-Y h:i A', strtotime($receipt['payment_date'])); ?></td>
                    </tr>
                    <tr>
                        <th>Payment Method</th>
                        <td><?php echo htmlspecialchars($receipt['payment_method']); ?></td>
                    </tr>
                    <tr>
                        <th>Transaction Reference</th>
                        <td><?php echo htmlspecialchars($receipt['transaction_ref'] ?? 'N/A'); ?></td>
                    </tr>
                </table>

                <hr>

                <!-- Student Information -->
                <h6><i class="fas fa-user-graduate"></i> Student Information</h6>
                <table class="table table-bordered table-details">
                    <tr>
                        <th style="width: 35%;">Student Name</th>
                        <td><strong><?php echo htmlspecialchars($receipt['full_name']); ?></strong></td>
                    </tr>
                    <tr>
                        <th>Father Name</th>
                        <td><?php echo htmlspecialchars($receipt['father_name'] ?? 'N/A'); ?></td>
                    </tr>
                    <tr>
                        <th>Roll No</th>
                        <td><?php echo htmlspecialchars($receipt['roll_no'] ?? 'N/A'); ?></td>
                    </tr>
                    <tr>
                        <th>Program</th>
                        <td><?php echo htmlspecialchars($receipt['department_name']); ?></td>
                    </tr>
                    <tr>
                        <th>Semester</th>
                        <td><?php echo htmlspecialchars($receipt['semester_name']); ?></td>
                    </tr>
                    <tr>
                        <th>Session</th>
                        <td><?php echo htmlspecialchars($receipt['session_name']); ?></td>
                    </tr>
                </table>

                <hr>

                <!-- Fee Details -->
                <h6><i class="fas fa-money-bill-wave"></i> Fee Details</h6>
                <table class="table table-bordered table-details">
                    <tr>
                        <th style="width: 35%;">Total Fee</th>
                        <td><strong>PKR <?php echo number_format($receipt['total_amount'], 2); ?></strong></td>
                    </tr>
                    <tr>
                        <th>Amount Paid</th>
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
                                <span class="badge bg-warning text-dark">Partially Paid</span>
                            <?php else: ?>
                                <span class="badge bg-danger">Unpaid</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                </table>

                <div class="receipt-footer">
                    <small>This is a system-generated receipt. Valid without signature.</small><br>
                    <small>Generated on: <?php echo date('d-M-Y h:i A'); ?></small>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>