<?php
// LMS Sync Helper - Finance Module
// Synchronizes finance data (fees, payments) with the LMS lms_fees table.

if (!function_exists('syncFeeToLMS')) {

function getUserIdForStudent($conn, int $student_id): ?int {
    // Try direct user_id column on students
    $checkCol = @mysqli_query($conn, "SHOW COLUMNS FROM students LIKE 'user_id'");
    if ($checkCol && mysqli_num_rows($checkCol) > 0) {
        $sql = "SELECT user_id FROM students WHERE student_id = '$student_id'";
        $result = mysqli_query($conn, $sql);
        if ($result && mysqli_num_rows($result) > 0) {
            $row = mysqli_fetch_assoc($result);
            if (!empty($row['user_id'])) return (int) $row['user_id'];
        }
    }
    // Fallback: Match by email
    $sql = "SELECT u.user_id FROM users u JOIN students s ON s.email = u.email AND u.role_id = 4 WHERE s.student_id = '$student_id'";
    $result = mysqli_query($conn, $sql);
    if ($result && mysqli_num_rows($result) > 0) {
        return (int) mysqli_fetch_assoc($result)['user_id'];
    }
    // Fallback: Match by full_name for role_id=4
    $sql = "SELECT u.user_id FROM users u JOIN students s ON s.full_name = u.full_name AND u.role_id = 4 WHERE s.student_id = '$student_id' LIMIT 1";
    $result = mysqli_query($conn, $sql);
    if ($result && mysqli_num_rows($result) > 0) {
        return (int) mysqli_fetch_assoc($result)['user_id'];
    }
    return null;
}

function syncFeeToLMS($conn, int $student_id, float $total_amount, float $amount_paid, string $due_date): bool {
    $user_id = getUserIdForStudent($conn, $student_id);
    if (!$user_id) return false;

    // Check for existing lms_fees record for this student
    $checkSql = "SELECT fee_id FROM lms_fees WHERE student_user_id = '$user_id' ORDER BY fee_id DESC LIMIT 1";
    $checkResult = mysqli_query($conn, $checkSql);

    $status = ($amount_paid >= $total_amount) ? 'paid' : ($amount_paid > 0 ? 'partial' : 'unpaid');

    if ($checkResult && mysqli_num_rows($checkResult) > 0) {
        $row = mysqli_fetch_assoc($checkResult);
        $updateSql = "UPDATE lms_fees SET amount = '$total_amount', status = '$status', due_date = '$due_date' WHERE fee_id = '{$row['fee_id']}'";
        return mysqli_query($conn, $updateSql);
    } else {
        $insertSql = "INSERT INTO lms_fees (student_user_id, course_id, amount, status, due_date) VALUES ('$user_id', NULL, '$total_amount', '$status', '$due_date')";
        return mysqli_query($conn, $insertSql);
    }
}

function syncPaymentToLMS($conn, int $student_id, int $student_fee_id): void {
    $user_id = getUserIdForStudent($conn, $student_id);
    if (!$user_id) return;

    $feeSql = "SELECT total_amount, paid_amount FROM student_fee WHERE student_fee_id = '$student_fee_id'";
    $feeResult = mysqli_query($conn, $feeSql);
    if (!$feeResult || mysqli_num_rows($feeResult) == 0) return;

    $feeRow = mysqli_fetch_assoc($feeResult);
    $newStatus = 'unpaid';
    if ($feeRow['paid_amount'] >= $feeRow['total_amount']) {
        $newStatus = 'paid';
    } elseif ($feeRow['paid_amount'] > 0) {
        $newStatus = 'partial';
    }

    $updateSql = "UPDATE lms_fees SET status = '$newStatus', paid_at = IF('$newStatus' = 'paid', NOW(), paid_at) WHERE student_user_id = '$user_id'";
    mysqli_query($conn, $updateSql);
}

} // end if function_exists
?>
