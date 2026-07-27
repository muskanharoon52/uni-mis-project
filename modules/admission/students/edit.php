<?php
require_once __DIR__ . '/../config/database.php';
$page_title = 'Edit Student';
include __DIR__ . '/../includes/header.php';

$id = $_GET['id'] ?? 0;

$stmt = $pdo->prepare("SELECT * FROM admission_students WHERE id = ?");
$stmt->execute([$id]);
$student = $stmt->fetch();

if (!$student) {
    setFlash('error', 'Student not found');
    header('Location: index.php');
    exit();
}

$departments = $pdo->query("SELECT * FROM departments WHERE status='active' ORDER BY department_name")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $data = [
            'student_name' => sanitize($_POST['student_name']),
            'father_name' => sanitize($_POST['father_name']),
            'cnic_or_bform' => sanitize($_POST['cnic_or_bform']),
            'dob' => $_POST['dob'],
            'gender' => $_POST['gender'],
            'contact_no' => sanitize($_POST['contact_no']),
            'email' => sanitize($_POST['email']),
            'address' => sanitize($_POST['address']),
            'program_id' => $_POST['department_id'],
            'status' => $_POST['status']
        ];
        
        $sql = "UPDATE admission_students SET ";
        $set_parts = [];
        foreach ($data as $key => $value) {
            $set_parts[] = "$key = :$key";
        }
        $sql .= implode(", ", $set_parts) . " WHERE id = :id";
        $data['id'] = $id;
        
        $stmt = $pdo->prepare($sql);
        if ($stmt->execute($data)) {
            setFlash('success', 'Student updated successfully!');
            header('Location: view.php?id=' . $id);
            exit();
        }
    } catch (PDOException $e) {
        setFlash('error', 'Database Error: ' . $e->getMessage());
    }
}
?>
<div class="page-header">
    <div class="page-header-left">
        <h4><i class="fas fa-user-edit"></i> Edit Student Profile</h4>
    </div>
    <div class="page-header-actions">
        <a href="view.php?id=<?= $id ?>" class="btn btn-outline"><i class="fas fa-arrow-left"></i> Back</a>
    </div>
</div>

<?php
$flash = getFlash();
if ($flash): ?>
    <div class="alert alert-<?= $flash['type'] === 'error' ? 'error' : 'success' ?>">
        <?= $flash['message'] ?>
    </div>
<?php endif; ?>

<div class="form-container">
    <form method="POST">
        <h6 style="font-size:.92rem;font-weight:700;color:var(--navy);margin-bottom:16px;padding-bottom:8px;border-bottom:1px solid var(--border);">Personal Information</h6>
        
        <div class="form-row">
            <div class="form-group">
                <label>Student Name *</label>
                <input type="text" name="student_name" value="<?= htmlspecialchars($student['student_name'] ?? '') ?>" required>
            </div>
            <div class="form-group">
                <label>Father Name *</label>
                <input type="text" name="father_name" value="<?= htmlspecialchars($student['father_name'] ?? '') ?>" required>
            </div>
        </div>
        
        <div class="form-row">
            <div class="form-group">
                <label>CNIC/B-Form *</label>
                <input type="text" name="cnic_or_bform" value="<?= htmlspecialchars($student['cnic_or_bform'] ?? '') ?>" required>
            </div>
            <div class="form-group">
                <label>Date of Birth *</label>
                <input type="date" name="dob" value="<?= htmlspecialchars($student['dob'] ?? '') ?>" required>
            </div>
        </div>
        
        <div class="form-row">
            <div class="form-group">
                <label>Gender *</label>
                <select name="gender" required>
                    <option value="Male" <?= ($student['gender'] ?? '') == 'Male' ? 'selected' : '' ?>>Male</option>
                    <option value="Female" <?= ($student['gender'] ?? '') == 'Female' ? 'selected' : '' ?>>Female</option>
                    <option value="Other" <?= ($student['gender'] ?? '') == 'Other' ? 'selected' : '' ?>>Other</option>
                </select>
            </div>
            <div class="form-group">
                <label>Email *</label>
                <input type="email" name="email" value="<?= htmlspecialchars($student['email'] ?? '') ?>" required>
            </div>
        </div>
        
        <div class="form-row">
            <div class="form-group">
                <label>Contact Number *</label>
                <input type="text" name="contact_no" value="<?= htmlspecialchars($student['contact_no'] ?? '') ?>" required>
            </div>
            <div class="form-group">
                <label>Address</label>
                <textarea name="address" rows="2"><?= htmlspecialchars($student['address'] ?? '') ?></textarea>
            </div>
        </div>
        
        <h6 style="font-size:.92rem;font-weight:700;color:var(--navy);margin-top:20px;margin-bottom:16px;padding-bottom:8px;border-bottom:1px solid var(--border);">Academic & Status Assignment</h6>
        
        <div class="form-row">
            <div class="form-group">
                <label>Department *</label>
                <select name="department_id" required>
                    <option value="">-- Select Department --</option>
                    <?php foreach($departments as $d): ?>
                    <option value="<?= $d['department_id'] ?>" <?= ($student['program_id'] ?? '') == $d['department_id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($d['department_name']) ?> (<?= htmlspecialchars($d['department_code'] ?? 'N/A') ?>)
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Status</label>
                <select name="status">
                    <option value="active" <?= ($student['status'] ?? '') == 'active' ? 'selected' : '' ?>>Active</option>
                    <option value="inactive" <?= ($student['status'] ?? '') == 'inactive' ? 'selected' : '' ?>>Inactive</option>
                    <option value="graduated" <?= ($student['status'] ?? '') == 'graduated' ? 'selected' : '' ?>>Graduated</option>
                    <option value="suspended" <?= ($student['status'] ?? '') == 'suspended' ? 'selected' : '' ?>>Suspended</option>
                </select>
            </div>
        </div>
        
        <div class="form-actions">
            <a href="view.php?id=<?= $id ?>" class="btn btn-outline">Cancel</a>
            <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Update Student</button>
        </div>
    </form>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
