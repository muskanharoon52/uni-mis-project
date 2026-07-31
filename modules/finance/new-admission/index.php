<?php
$pageTitle = 'New Admissions';
include_once __DIR__ . '/../includes/header.php';

if (!isset($_SESSION['user_id'])) { header('Location: /uni-mis-project/'); exit(); }
if ($_SESSION['role_id'] != 3 && $_SESSION['role_id'] != 1) { header('Location: /uni-mis-project/'); exit(); }

$error = '';
$success = '';
$search_term = isset($_GET['search']) ? trim($_GET['search']) : '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['mark_paid'])) {
    $adm_id = intval($_POST['adm_id']);

    $adm_result = mysqli_query($conn, "SELECT * FROM admission_students WHERE id = '$adm_id' AND fee_paid = 0");
    if (mysqli_num_rows($adm_result) == 0) {
        $error = "Student not found or fee already paid.";
    } else {
        $adm = mysqli_fetch_assoc($adm_result);
        mysqli_begin_transaction($conn);
        try {
            $now = date('Y-m-d H:i:s');
            mysqli_query($conn, "UPDATE admission_students SET fee_paid = 1, fee_paid_at = '$now' WHERE id = '$adm_id'");

            $app_id = $adm['application_id'] ? intval($adm['application_id']) : 'NULL';
            $program_id = intval($adm['program_id'] ?? 1);
            $session_id = 1;
            $semester_id = 1;

            if ($adm['application_id']) {
                $app_result = mysqli_query($conn, "SELECT session_id, applied_semester_id FROM admission_applications WHERE application_id = '{$adm['application_id']}'");
                if ($app_result && mysqli_num_rows($app_result) > 0) {
                    $app_row = mysqli_fetch_assoc($app_result);
                    if ($app_row['session_id']) $session_id = intval($app_row['session_id']);
                    if ($app_row['applied_semester_id']) $semester_id = intval($app_row['applied_semester_id']);
                }
            }

            $login_result = mysqli_query($conn, "SELECT MAX(CAST(login_id AS UNSIGNED)) as max_login FROM users");
            $login_row = mysqli_fetch_assoc($login_result);
            $login_id = ($login_row['max_login'] ?? 10000) + 1;

            $sid_result = mysqli_query($conn, "SELECT MAX(student_id) as max_sid FROM students");
            $sid_row = mysqli_fetch_assoc($sid_result);
            $next_sid = ($sid_row['max_sid'] ?? 0) + 1;

            $name_slug = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $adm['full_name']));
            $username = substr($name_slug, 0, 10) . $login_id;
            $password_hash = password_hash('student123', PASSWORD_DEFAULT);
            $email = $adm['email'] ?? $username . '@university.edu';
            $phone = $adm['contact_no'] ?? '';

            $user_insert = "INSERT INTO users (full_name, username, login_id, email, phone, password_hash, role_id, department_id, status)
                VALUES ('{$adm['full_name']}', '$username', '$login_id', '$email', '$phone', '$password_hash', 4, '$program_id', 'Active')";
            mysqli_query($conn, $user_insert);
            $new_user_id = mysqli_insert_id($conn);

            $roll_no = date('Y') . '-' . $program_id . '-' . str_pad($next_sid, 3, '0', STR_PAD_LEFT);
            $batch_year = date('Y');
            $admission_date = date('Y-m-d');

            $student_insert = "INSERT INTO students (application_id, roll_no, full_name, father_name, cnic_or_bform, dob, gender, contact_no, email, address, program_id, admission_session_id, current_session_id, current_semester_id, batch_year, admission_date, status, user_id)
                VALUES ($app_id, '$roll_no', '{$adm['full_name']}', '{$adm['father_name']}', '{$adm['cnic_or_bform']}', '{$adm['dob']}', '{$adm['gender']}', '$phone', '$email', '{$adm['address']}', '$program_id', '$session_id', '$session_id', '$semester_id', '$batch_year', '$admission_date', 'Active', '$new_user_id')";
            mysqli_query($conn, $student_insert);

            if ($adm['application_id']) {
                mysqli_query($conn, "UPDATE admission_applications SET application_status = 'Admitted' WHERE application_id = '{$adm['application_id']}'");
            }

            mysqli_commit($conn);
            $success = "Admission fee marked as paid. Student registered successfully as <strong>{$adm['full_name']}</strong> (Roll No: $roll_no).";
        } catch (Exception $e) {
            mysqli_rollback($conn);
            $error = "Error: " . $e->getMessage();
        }
    }
}

$where = "WHERE fee_paid = 0";
if (!empty($search_term)) {
    $search = mysqli_real_escape_string($conn, $search_term);
    $where .= " AND (student_id LIKE '%$search%' OR full_name LIKE '%$search%' OR student_name LIKE '%$search%')";
}
$students = mysqli_query($conn, "SELECT asd.*, p.program_name
    FROM admission_students asd
    LEFT JOIN programs p ON p.program_id = asd.program_id
    $where ORDER BY asd.id DESC");
?>

<?php if ($error): ?><div class="alert alert-error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
<?php if ($success): ?><div class="alert alert-success"><?= $success ?></div><?php endif; ?>

<div class="card">
    <div class="card-header">
        <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:10px;">
            <h3>New Admissions (Pending Fee)</h3>
            <span class="badge badge-outline"><?= mysqli_num_rows($students) ?> pending</span>
        </div>
    </div>
    <form method="GET" style="padding:12px 22px;border-bottom:1px solid var(--border);background:#f9fafb;">
        <div style="display:flex;gap:8px;">
            <input type="text" name="search" placeholder="Search by Student ID or Name..." value="<?= htmlspecialchars($search_term) ?>" style="flex:1;">
            <button class="btn btn-primary" type="submit">Search</button>
            <?php if (!empty($search_term)): ?>
                <a href="index.php" class="btn btn-ghost">Clear</a>
            <?php endif; ?>
        </div>
    </form>
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>Student ID</th>
                    <th>Name</th>
                    <th>Program</th>
                    <th>Contact</th>
                    <th>Applied Date</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (mysqli_num_rows($students) > 0): ?>
                    <?php while ($row = mysqli_fetch_assoc($students)): ?>
                        <tr>
                            <td style="font-weight:600;"><?= htmlspecialchars($row['student_id'] ?? 'N/A') ?></td>
                            <td><?= htmlspecialchars($row['full_name']) ?></td>
                            <td><?= htmlspecialchars($row['program_name'] ?? 'N/A') ?></td>
                            <td class="muted"><?= htmlspecialchars($row['contact_no'] ?? 'N/A') ?></td>
                            <td class="muted"><?= $row['created_at'] ? date('M j, Y', strtotime($row['created_at'])) : 'N/A' ?></td>
                            <td>
                                <form method="POST" onsubmit="return confirm('Mark admission fee as paid for <?= htmlspecialchars($row['full_name']) ?>? This will register the student.');">
                                    <input type="hidden" name="adm_id" value="<?= $row['id'] ?>">
                                    <button type="submit" name="mark_paid" class="btn btn-sm btn-primary">
                                        Mark Fee Paid &amp; Register
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="6" class="muted text-center" style="padding:24px;">No pending admissions found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include_once __DIR__ . '/../includes/footer.php'; ?>
