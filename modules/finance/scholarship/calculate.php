<?php
require_once __DIR__ . '/../../../config/db_connect.php';
require_once __DIR__ . '/../includes/auth.php';

if (!isLoggedIn()) { header('Location: ' . BASE_URL . 'login.php'); exit; }

$user = getCurrentUser();
$role = strtolower($user['role_name'] ?? 'user');

if (!in_array($role, ['sso', 'admin'])) {
    header('Location: ' . BASE_URL . 'dashboard.php');
    exit;
}

$conn = getConnection();
$message = '';
$message_type = '';

$all_students = [];
$students_query = "SELECT s.student_id, u.full_name, s.gpa, s.scholarship_percentage 
                   FROM students s LEFT JOIN users u ON s.user_id = u.user_id ORDER BY u.full_name";
$students_result = mysqli_query($conn, $students_query);
if ($students_result) { while ($row = mysqli_fetch_assoc($students_result)) { $all_students[] = $row; } }

$rules = [];
$rules_result = mysqli_query($conn, "SELECT * FROM scholarship_rules WHERE status = 'Active' ORDER BY min_gpa");
if ($rules_result) { while ($row = mysqli_fetch_assoc($rules_result)) { $rules[] = $row; } }

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $student_id = mysqli_real_escape_string($conn, $_POST['student_id']);
    $gpa = floatval($_POST['gpa']);
    $semester_id = (int)$_POST['semester_id'];
    $user_id = $_SESSION['user_id'];
    
    $scholarship_percentage = 0;
    foreach ($rules as $rule) {
        if ($gpa >= $rule['min_gpa'] && $gpa <= $rule['max_gpa']) {
            $scholarship_percentage = $rule['scholarship_percentage'];
            break;
        }
    }
    
    $update_query = "UPDATE students SET gpa = $gpa, scholarship_percentage = $scholarship_percentage WHERE student_id = '$student_id'";
    if (mysqli_query($conn, $update_query)) {
        mysqli_query($conn, "INSERT INTO scholarships (student_id, scholarship_type, awarding_body, semester_id, discount_kind, discount_value, approved_by, remarks, status) VALUES ('$student_id', 'Merit', 'GPA Based', $semester_id, 'Percentage', $scholarship_percentage, $user_id, 'Auto-calculated from GPA: $gpa', 'Active')");
        $message = "Scholarship calculated! Student: $student_id | GPA: $gpa | Scholarship: $scholarship_percentage%";
        $message_type = 'success';
    } else {
        $message = "Error: " . mysqli_error($conn);
        $message_type = 'error';
    }
}

$pageTitle = 'GPA Based Scholarship';
include __DIR__ . '/../includes/header.php';
?>

<div style="margin-bottom:16px;">
    <a href="index.php" class="btn btn-ghost" style="font-size:.82rem;">&#8592; Back to Fee Management</a>
</div>

<?php if ($message): ?><div class="alert alert-<?= $message_type ?>"><?= $message ?></div><?php endif; ?>

<?php if (!empty($rules)): ?>
<div class="card" style="margin-bottom:20px;">
    <div class="card-header"><h3>Scholarship Rules</h3></div>
    <div style="padding:22px;">
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:14px;">
            <?php foreach ($rules as $rule): ?>
                <div style="background:var(--bg);padding:16px;border-radius:var(--radius);text-align:center;border-left:4px solid var(--success);">
                    <div style="font-size:.88rem;font-weight:600;margin-bottom:4px;">GPA: <?= $rule['min_gpa'] ?> - <?= $rule['max_gpa'] ?></div>
                    <div style="font-size:1.4rem;font-weight:700;color:var(--success);"><?= $rule['scholarship_percentage'] ?>%</div>
                    <div class="muted" style="font-size:.78rem;">Scholarship</div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>
<?php endif; ?>

<div class="card" style="max-width:640px;margin-bottom:20px;">
    <div class="card-header"><h3>Calculate Student Scholarship</h3></div>
    <form method="POST">
        <div style="padding:22px;">
            <div class="inline-form-row" style="grid-template-columns:1fr 1fr;">
                <div class="field" style="margin-bottom:0;">
                    <label>Select Student</label>
                    <select name="student_id" required>
                        <option value="">-- Select Student --</option>
                        <?php foreach ($all_students as $student): ?>
                            <option value="<?= $student['student_id'] ?>"><?= htmlspecialchars($student['full_name'] . ' (' . $student['student_id'] . ')') ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="field" style="margin-bottom:0;">
                    <label>Semester</label>
                    <select name="semester_id" required>
                        <option value="">-- Select Semester --</option>
                        <?php for ($i = 1; $i <= 8; $i++): ?>
                            <option value="<?= $i ?>"><?= $i ?><?= ($i == 1) ? 'st' : (($i == 2) ? 'nd' : (($i == 3) ? 'rd' : 'th')) ?> Semester</option>
                        <?php endfor; ?>
                    </select>
                </div>
            </div>
            <div class="inline-form-row" style="grid-template-columns:1fr 1fr;">
                <div class="field" style="margin-bottom:0;">
                    <label>GPA</label>
                    <input type="number" name="gpa" id="gpa_input" placeholder="e.g., 3.50" min="0" max="4" step="0.01" required>
                    <p class="muted" style="margin-top:4px;font-size:.78rem;">Enter GPA between 0.00 and 4.00</p>
                </div>
                <div class="field" style="margin-bottom:0;">
                    <label>Scholarship</label>
                    <input type="text" id="scholarship_display" readonly placeholder="Will auto-calculate" style="font-size:1.05rem;font-weight:700;">
                </div>
            </div>
            <div class="actions" style="margin-top:20px;">
                <button type="submit" class="btn btn-primary">Calculate & Assign</button>
                <a href="index.php" class="btn btn-ghost">Cancel</a>
            </div>
        </div>
    </form>
</div>

<?php if (!empty($all_students)): ?>
<div class="card">
    <div class="card-header"><h3>All Students with GPA</h3></div>
    <div class="table-responsive">
        <table>
            <thead><tr><th>#</th><th>Student</th><th>Student ID</th><th>GPA</th><th>Scholarship</th></tr></thead>
            <tbody>
                <?php $i = 1; foreach ($all_students as $student):
                    $gpa = $student['gpa'] ?? 0;
                    $sch = $student['scholarship_percentage'] ?? 0;
                    if ($gpa >= 3.76) $gs = 'badge-active';
                    elseif ($gpa >= 3.33) $gs = 'badge-pending';
                    elseif ($gpa > 0) $gs = 'badge-inactive';
                    else $gs = 'badge-outline';
                ?>
                <tr>
                    <td><?= $i++ ?></td>
                    <td style="font-weight:600;"><?= htmlspecialchars($student['full_name'] ?? 'N/A') ?></td>
                    <td class="muted"><?= htmlspecialchars($student['student_id'] ?? 'N/A') ?></td>
                    <td><span class="badge <?= $gs ?>"><?= $gpa > 0 ? number_format($gpa, 2) : 'Not Set' ?></span></td>
                    <td>
                        <?php if ($sch > 0): ?>
                            <span class="badge badge-active"><?= $sch ?>%</span>
                        <?php else: ?>
                            <span class="badge badge-outline">None</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var g = document.getElementById('gpa_input');
    var d = document.getElementById('scholarship_display');
    var rules = <?php echo json_encode($rules); ?>;
    g.addEventListener('input', function() {
        var v = parseFloat(this.value);
        if (isNaN(v)) { d.value = 'Enter GPA'; d.style.color = 'var(--muted)'; return; }
        var p = 0;
        for (var i = 0; i < rules.length; i++) { if (v >= rules[i].min_gpa && v <= rules[i].max_gpa) { p = rules[i].scholarship_percentage; break; } }
        if (p > 0) { d.value = p + '% Scholarship'; d.style.color = 'var(--success)'; }
        else if (v > 0 && v < 3.33) { d.value = 'No Scholarship (Below 3.33 GPA)'; d.style.color = 'var(--danger)'; }
        else { d.value = 'Enter valid GPA'; d.style.color = 'var(--muted)'; }
    });
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
