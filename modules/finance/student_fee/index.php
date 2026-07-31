<?php
$pageTitle = 'Student Fee Dashboard';
include __DIR__ . '/../includes/header.php';

// =============================================
// HANDLE TABLE CREATION (If user clicks the button)
// =============================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create_fee_table') {
    $create_sql = "
        CREATE TABLE IF NOT EXISTS `student_fees` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `student_id` int(11) NOT NULL,
            `total_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
            `paid_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
            `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        
        CREATE TABLE IF NOT EXISTS `student_fee_items` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `student_fee_id` int(11) NOT NULL,
            `fee_structure_id` int(11) NOT NULL,
            `fee_type` varchar(100) NOT NULL,
            `amount` decimal(10,2) NOT NULL DEFAULT 0.00,
            PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ";
    
    if (mysqli_multi_query($conn, $create_sql)) {
        $success_msg = "✅ Student Fee tables created successfully! Refresh this page to use the system.";
    } else {
        $error_msg = "Error creating tables: " . mysqli_error($conn);
    }
}

// =============================================
// HANDLE REMOVE FEE HEAD ACTION
// =============================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'remove_fee_item') {
    $fee_item_id = (int)$_POST['fee_item_id'];
    $student_fee_id = (int)$_POST['student_fee_id'];
    $student_id = (int)$_POST['student_id'];

    // Start transaction
    mysqli_begin_transaction($conn);
    try {
        // 1. Get the amount of the fee being removed
        $get_item = mysqli_query($conn, "SELECT amount FROM student_fee_items WHERE id = $fee_item_id");
        $item_data = mysqli_fetch_assoc($get_item);
        $remove_amount = $item_data['amount'];

        // 2. Delete the fee item
        mysqli_query($conn, "DELETE FROM student_fee_items WHERE id = $fee_item_id");

        // 3. Update the Total Amount in the main fees table
        mysqli_query($conn, "UPDATE student_fees SET total_amount = total_amount - $remove_amount WHERE id = $student_fee_id");

        mysqli_commit($conn);
        $success_msg = "Fee head removed successfully. Amount adjusted.";
    } catch (Exception $e) {
        mysqli_rollback($conn);
        $error_msg = "Error removing fee: " . $e->getMessage();
    }
}

// =============================================
// GET SEARCH PARAMETERS
// =============================================
$search_student_id = isset($_GET['student_id']) ? trim($_GET['student_id']) : '';

// =============================================
// BUILD THE SEARCH QUERY (With Try/Catch to prevent crash)
// =============================================
$student_data = null;
$fee_record = null;
$fee_items = [];
$table_missing = false;

if (!empty($search_student_id)) {
    // FIX: Smart search - handles ID numbers (9824) and Student IDs (STU-...)
    if (is_numeric($search_student_id)) {
        // If user typed just a number, search by Internal ID first
        $student_query = "SELECT s.*, d.department_name 
                          FROM admission_students s 
                          LEFT JOIN departments d ON s.program_id = d.department_id 
                          WHERE s.id = '" . mysqli_real_escape_string($conn, $search_student_id) . "' 
                          OR s.student_id = '" . mysqli_real_escape_string($conn, $search_student_id) . "'";
    } else {
        // If user typed a text string (like STU-2026), search by Student ID
        $student_query = "SELECT s.*, d.department_name 
                          FROM admission_students s 
                          LEFT JOIN departments d ON s.program_id = d.department_id 
                          WHERE s.student_id = '" . mysqli_real_escape_string($conn, $search_student_id) . "'";
    }
    
    $student_result = mysqli_query($conn, $student_query);
    $student_data = mysqli_fetch_assoc($student_result);

    if ($student_data) {
        // 2. Find their overall Fee Record (Wrapped in Try/Catch)
        try {
            $fee_query = "SELECT * FROM student_fees WHERE student_id = " . $student_data['id'] . " ORDER BY id DESC LIMIT 1";
            $fee_result = mysqli_query($conn, $fee_query);
            
            if ($fee_result === false) {
                // If the query fails (because the table doesn't exist), set a flag
                $table_missing = true;
            } else {
                $fee_record = mysqli_fetch_assoc($fee_result);
            }
        } catch (Exception $e) {
            $table_missing = true;
        }

        // 3. Get their Fee Head Breakdown (Only if main fee exists)
        if ($fee_record) {
            $item_query = "SELECT * FROM student_fee_items WHERE student_fee_id = " . $fee_record['id'];
            $item_result = mysqli_query($conn, $item_query);
            while($row = mysqli_fetch_assoc($item_result)) {
                $fee_items[] = $row;
            }
        }
    }
}
?>

<?php if (isset($_GET['msg'])): ?>
    <div class="alert alert-success"><?= htmlspecialchars($_GET['msg']) ?></div>
<?php endif; ?>
<?php if (isset($success_msg)): ?>
    <div class="alert alert-success"><?= htmlspecialchars($success_msg) ?></div>
<?php endif; ?>
<?php if (isset($error_msg)): ?>
    <div class="alert alert-error"><?= htmlspecialchars($error_msg) ?></div>
<?php endif; ?>

<div class="card">
    <div class="card-header">
        <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:10px;">
            <div style="display:flex;align-items:center;gap:15px;flex-wrap:wrap;">
                <h3>Student Financial Dashboard</h3>
            </div>
        </div>
    </div>

    <!-- ============================================= -->
    <!-- STUDENT SEARCH FORM -->
    <!-- ============================================= -->
    <div style="padding:20px; border-bottom:1px solid var(--border); background:#f9fafb;">
        <form method="GET" action="" style="display:flex;gap:10px;align-items:center;flex-wrap:wrap;">
            <div style="flex-grow:1; min-width:250px;">
                <label style="font-size:12px;font-weight:600;color:#374151;display:block;margin-bottom:4px;">Enter Student ID / Roll No</label>
                <input type="text" name="student_id" value="<?= htmlspecialchars($search_student_id) ?>" 
                       placeholder="e.g. 9824 or STU-2026-8858" 
                       style="width:100%;padding:8px 12px;border:1px solid #d1d5db;border-radius:4px;font-size:14px;">
            </div>
            <div style="padding-top:22px;">
                <button type="submit" class="btn btn-primary" style="padding:8px 20px;">
                    <i class="fas fa-search"></i> Search Student
                </button>
                <?php if (!empty($search_student_id)): ?>
                    <a href="index.php" class="btn btn-outline" style="padding:8px 16px;margin-left:5px;">
                        <i class="fas fa-times"></i> Clear
                    </a>
                <?php endif; ?>
            </div>
        </form>
    </div>
    <!-- ============================================= -->

    <div class="card-content">
        <?php if ($table_missing && !empty($search_student_id)): ?>
            <div class="alert alert-warning" style="margin:20px;">
                <h4 style="margin:0 0 10px 0;"><i class="fas fa-database"></i> Missing Database Tables</h4>
                <p>The required tables <strong>'student_fees'</strong> and <strong>'student_fee_items'</strong> are missing from your database.</p>
                
                <form method="POST" action="" onsubmit="return confirm('This will create the necessary tables in your database. Continue?');">
                    <input type="hidden" name="action" value="create_fee_table">
                    <button type="submit" class="btn btn-primary" style="background:#2563eb;color:#fff;border:none;padding:8px 16px;border-radius:4px;cursor:pointer;">
                        <i class="fas fa-plus-circle"></i> Create Missing Tables Now
                    </button>
                </form>
            </div>
        <?php endif; ?>

        <?php if (!empty($search_student_id) && !$student_data && !$table_missing): ?>
            <div class="alert alert-error" style="margin:20px;">
                <i class="fas fa-exclamation-circle"></i> No student found with ID: <strong><?= htmlspecialchars($search_student_id) ?></strong>
            </div>
        <?php elseif ($student_data): ?>
            
            <!-- Student Summary Header -->
            <div style="background:#f0fdf4; border:1px solid #bbf7d0; border-radius:8px; padding:15px 20px; margin-bottom:20px;">
                <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:10px;">
                    <div>
                        <h4 style="margin:0; color:#065f46;"><?= htmlspecialchars($student_data['student_name']) ?></h4>
                        <p style="margin:4px 0 0 0; font-size:.9rem; color:#047857;">
                            <strong>Roll No:</strong> <?= htmlspecialchars($student_data['student_id']) ?> &nbsp;|&nbsp; 
                            <strong>Program:</strong> <?= htmlspecialchars($student_data['department_name'] ?? 'N/A') ?>
                        </p>
                    </div>
                    <div>
                        <a href="generate.php?student_id=<?= $student_data['id'] ?>" class="btn btn-primary btn-sm">
                            <i class="fas fa-plus"></i> Add New Fee
                        </a>
                    </div>
                </div>
            </div>

            <!-- Financial Summary -->
            <?php if ($fee_record): ?>
                <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(150px, 1fr)); gap:15px; margin-bottom:20px;">
                    <div style="background:#fff; border:1px solid #e5e7eb; border-radius:8px; padding:15px; text-align:center;">
                        <div style="font-size:.75rem;color:#6b7280;text-transform:uppercase;">Total Dues</div>
                        <div style="font-size:1.4rem;font-weight:700;color:#1f2937;">PKR <?= number_format($fee_record['total_amount'] ?? 0, 2) ?></div>
                    </div>
                    <div style="background:#fff; border:1px solid #e5e7eb; border-radius:8px; padding:15px; text-align:center;">
                        <div style="font-size:.75rem;color:#6b7280;text-transform:uppercase;">Paid Amount</div>
                        <div style="font-size:1.4rem;font-weight:700;color:#10b981;">PKR <?= number_format($fee_record['paid_amount'] ?? 0, 2) ?></div>
                    </div>
                    <div style="background:#fff; border:1px solid #e5e7eb; border-radius:8px; padding:15px; text-align:center;">
                        <div style="font-size:.75rem;color:#6b7280;text-transform:uppercase;">Remaining</div>
                        <?php $remaining = ($fee_record['total_amount'] ?? 0) - ($fee_record['paid_amount'] ?? 0); ?>
                        <div style="font-size:1.4rem;font-weight:700;color:#ef4444;">PKR <?= number_format($remaining, 2) ?></div>
                    </div>
                </div>

                <!-- Fee Heads Breakdown & Remove Actions -->
                <h5 style="margin-top:10px; border-bottom:1px solid #e5e7eb; padding-bottom:10px;">Fee Heads Breakdown</h5>
                
                <?php if (empty($fee_items)): ?>
                    <p class="muted text-center" style="padding:20px;">No specific fee heads found for this record.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table style="width:100%; border-collapse:collapse;">
                            <thead>
                                <tr style="background:#f9fafb;">
                                    <th style="text-align:left; padding:10px;">Fee Head / Type</th>
                                    <th style="text-align:right; padding:10px;">Amount</th>
                                    <th style="text-align:center; padding:10px;">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($fee_items as $item): ?>
                                <tr style="border-bottom:1px solid #f3f4f6;">
                                    <td style="padding:10px; font-weight:500;"><?= htmlspecialchars($item['fee_type']) ?></td>
                                    <td style="padding:10px; text-align:right; font-weight:600;">PKR <?= number_format($item['amount'], 2) ?></td>
                                    <td style="padding:10px; text-align:center;">
                                        <form method="POST" action="" onsubmit="return confirm('Are you sure you want to remove this fee head (<?= htmlspecialchars($item['fee_type']) ?>)? This will lower the total due.');">
                                            <input type="hidden" name="action" value="remove_fee_item">
                                            <input type="hidden" name="fee_item_id" value="<?= $item['id'] ?>">
                                            <input type="hidden" name="student_fee_id" value="<?= $fee_record['id'] ?>">
                                            <input type="hidden" name="student_id" value="<?= $student_data['id'] ?>">
                                            <button type="submit" class="btn btn-danger btn-sm" style="background:#ef4444; color:#fff; border:none; padding:4px 10px; border-radius:4px; cursor:pointer;">
                                                <i class="fas fa-trash"></i> Remove
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>

            <?php else: ?>
                <div class="alert alert-info" style="margin-top:10px;">
                    <i class="fas fa-info-circle"></i> This student does not have any generated fees yet. 
                    <a href="generate.php?student_id=<?= $student_data['id'] ?>" style="font-weight:600;">Generate a fee now</a>.
                </div>
            <?php endif; ?>

        <?php elseif (empty($search_student_id)): ?>
            <div class="empty-state" style="padding:40px 0; text-align:center; color:var(--muted);">
                <i class="fas fa-search" style="font-size:3rem; margin-bottom:15px; opacity:0.4;"></i>
                <h5 style="color:#111827;">Search for a Student</h5>
                <p>Enter a Student ID (e.g. 9824 or STU-2026-8858) in the search bar above to view their financial summary and detailed fee heads.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include_once __DIR__ . '/../includes/footer.php'; ?>