<?php
$pageTitle = 'New Admissions';
include_once __DIR__ . '/../includes/header.php';

if (!isset($_SESSION['user_id'])) { header('Location: /uni-mis-project/'); exit(); }
if ($_SESSION['role_id'] != 3 && $_SESSION['role_id'] != 1 && $_SESSION['role_id'] != 7) { header('Location: /uni-mis-project/'); exit(); }

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

            // Mark the admission fee as paid
            mysqli_query($conn, "UPDATE admission_students SET fee_paid = 1, fee_paid_at = '$now' WHERE id = '$adm_id'");

            $generated_credentials = null;
            $app_id = (int) ($adm['application_id'] ?? 0);

            if ($app_id > 0) {
                // Update the application status to waiting for enrollment
                mysqli_query($conn, "UPDATE admission_applications SET application_status = 'Fee Paid' WHERE application_id = '$app_id'");

                // =============================================
                // GENERATE LMS LOGIN CREDENTIALS
                // The student account (users + students) is created
                // here, right after fee confirmation, so the new
                // student can log in to the portal / LMS.
                // =============================================
                $existing = mysqli_query($conn, "SELECT user_id FROM students WHERE application_id = $app_id LIMIT 1");
                if ($existing && mysqli_num_rows($existing) === 0) {
                    $det = mysqli_query($conn, "
                        SELECT aa.email, aa.contact_no, aa.address, aa.father_name, aa.cnic_or_bform,
                               aa.dob, aa.gender, aa.session_id, aa.applied_semester_id,
                               asd.program_id, asd.full_name, asd.department_id
                        FROM admission_applications aa
                        JOIN admission_students asd ON asd.application_id = aa.application_id
                        WHERE aa.application_id = $app_id LIMIT 1
                    ");
                    $detRow = ($det && mysqli_num_rows($det) > 0) ? mysqli_fetch_assoc($det) : [];

                    $full_name = $detRow['full_name'] ?? $adm['full_name'] ?? 'Student';
                    $email = $detRow['email'] ?? $adm['email'] ?? '';
                    $phone = $detRow['contact_no'] ?? $adm['contact_no'] ?? '';
                    $program_id = (int) ($detRow['program_id'] ?? $adm['program_id'] ?? 1);

                    // Next numeric login id (keeps the same sequence as previous enrollments)
                    $login_row = mysqli_fetch_assoc(mysqli_query($conn, "SELECT MAX(CAST(login_id AS UNSIGNED)) AS max_login FROM users"));
                    $login_id = (int) ($login_row['max_login'] ?? 9000) + 1;
                    $username = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', (string) (explode(' ', $full_name)[0] ?? 'student'))) . $login_id;
                    $password_hash = password_hash('student123', PASSWORD_DEFAULT);

                    // Roll number (unique per program + year)
                    $seq = (int) (mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS c FROM students WHERE program_id = $program_id"))['c'] ?? 0) + 1;
                    $roll_no = '';
                    do {
                        $roll_no = date('Y') . '-' . $program_id . '-' . str_pad($seq, 3, '0', STR_PAD_LEFT);
                        $seq++;
                        $roll_chk = mysqli_query($conn, "SELECT roll_no FROM students WHERE roll_no = '$roll_no' LIMIT 1");
                    } while ($roll_chk && mysqli_num_rows($roll_chk) > 0);

                    $prog = mysqli_query($conn, "SELECT department_id FROM programs WHERE program_id = $program_id LIMIT 1");
                    $dept_id = ($prog && mysqli_num_rows($prog) > 0) ? (int) mysqli_fetch_assoc($prog)['department_id'] : null;

                    // Create the user login (role 4 = Student)
                    $stmt = $conn->prepare("INSERT INTO users (full_name, username, login_id, email, phone, password_hash, role_id, department_id, status) VALUES (?, ?, ?, ?, ?, ?, 4, ?, 'Active')");
                    $stmt->bind_param('ssisssi', $full_name, $username, $login_id, $email, $phone, $password_hash, $dept_id);
                    if (!$stmt->execute()) {
                        throw new Exception("Failed to create user account: " . $conn->error);
                    }
                    $new_user_id = (int) $conn->insert_id;
                    $stmt->close();

                    // Create the students registry row (required for LMS access)
                    $session_val = (int) ($detRow['session_id'] ?? 1);
                    $semester_val = (int) ($detRow['applied_semester_id'] ?? 1);
                    $father = $detRow['father_name'] ?? '';
                    $cnic = $detRow['cnic_or_bform'] ?? $adm['cnic_or_bform'] ?? '';
                    $dob = $detRow['dob'] ?? $adm['dob'] ?? null;
                    $gender = $detRow['gender'] ?? $adm['gender'] ?? 'Male';
                    $address = $detRow['address'] ?? $adm['address'] ?? '';

                    $stmt = $conn->prepare("INSERT INTO students (
                        application_id, roll_no, full_name, father_name, cnic_or_bform,
                        dob, gender, contact_no, email, address, program_id,
                        admission_session_id, current_session_id, current_semester_id,
                        batch_year, admission_date, status, user_id
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Active', ?)");
                    $batch_year = (int) date('Y');
                    $admission_date = date('Y-m-d');
                    $stmt->bind_param(
                        'isssssssssiiiiisi',
                        $app_id, $roll_no, $full_name, $father, $cnic,
                        $dob, $gender, $phone, $email, $address, $program_id,
                        $session_val, $session_val, $semester_val,
                        $batch_year, $admission_date, $new_user_id
                    );
                    if (!$stmt->execute()) {
                        throw new Exception("Failed to create student record: " . $conn->error);
                    }
                    $stmt->close();

                    // Mark as fully activated / admitted (link SSO account to admission record)
                    mysqli_query($conn, "UPDATE admission_students SET is_activated = 1, user_id = $new_user_id WHERE id = $adm_id");
                    mysqli_query($conn, "UPDATE admission_applications SET application_status = 'Admitted', status = 'admitted' WHERE application_id = $app_id");

                    $generated_credentials = ['username' => $username, 'password' => 'student123'];
                }
            }

            mysqli_commit($conn);

            $success = "Admission fee of 20,000 PKR marked as paid for <strong>" . htmlspecialchars($adm['full_name']) . "</strong>.";
            if ($generated_credentials) {
                $success .= "<br><br><strong>Student LMS login generated!</strong><br>"
                    . "Login ID: <code>" . htmlspecialchars($generated_credentials['username']) . "</code><br>"
                    . "Password: <code>" . htmlspecialchars($generated_credentials['password']) . "</code><br>"
                    . "<em>The student can now log in to the LMS portal using these credentials.</em>";
            } else {
                $success .= "<br>The student's login account already exists.";
            }
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
                                <form method="POST" onsubmit="return confirm('Mark 20,000 PKR admission fee as paid for <?= htmlspecialchars($row['full_name']) ?>?');">
                                    <input type="hidden" name="adm_id" value="<?= $row['id'] ?>">
                                    <button type="submit" name="mark_paid" class="btn btn-sm btn-primary">
                                        Mark Fee Paid (20,000 PKR)
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