<?php
require_once '../../config/database.php';
$page_title = 'Edit Student';
include '../../includes/header.php';

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
<div class="page-header"><h5><i class="fas fa-edit"></i> Edit Student</h5></div>

<?php
$flash = getFlash();
if ($flash): ?>
    <div class="alert alert-<?= $flash['type'] ?> alert-dismissible fade show">
        <?= $flash['message'] ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<form method="POST" class="row g-3">
    <div class="col-md-6">
        <label class="form-label">Student Name *</label>
        <input type="text" name="student_name" class="form-control" value="<?= $student['student_name'] ?? '' ?>" required>
    </div>
    
    <div class="col-md-6">
        <label class="form-label">Father Name *</label>
        <input type="text" name="father_name" class="form-control" value="<?= $student['father_name'] ?? '' ?>" required>
    </div>
    
    <div class="col-md-4">
        <label class="form-label">CNIC/B-Form *</label>
        <input type="text" name="cnic_or_bform" class="form-control" value="<?= $student['cnic_or_bform'] ?? '' ?>" required>
    </div>
    
    <div class="col-md-4">
        <label class="form-label">Date of Birth *</label>
        <input type="date" name="dob" class="form-control" value="<?= $student['dob'] ?? '' ?>" required>
    </div>
    
    <div class="col-md-4">
        <label class="form-label">Gender *</label>
        <select name="gender" class="form-select" required>
            <option value="Male" <?= ($student['gender'] ?? '') == 'Male' ? 'selected' : '' ?>>Male</option>
            <option value="Female" <?= ($student['gender'] ?? '') == 'Female' ? 'selected' : '' ?>>Female</option>
            <option value="Other" <?= ($student['gender'] ?? '') == 'Other' ? 'selected' : '' ?>>Other</option>
        </select>
    </div>
    
    <div class="col-md-6">
        <label class="form-label">Email *</label>
        <input type="email" name="email" class="form-control" value="<?= $student['email'] ?? '' ?>" required>
    </div>
    
    <div class="col-md-6">
        <label class="form-label">Contact Number *</label>
        <input type="text" name="contact_no" class="form-control" value="<?= $student['contact_no'] ?? '' ?>" required>
    </div>
    
    <div class="col-md-12">
        <label class="form-label">Address</label>
        <textarea name="address" class="form-control" rows="2"><?= $student['address'] ?? '' ?></textarea>
    </div>
    
    <div class="col-md-6">
        <label class="form-label">Department *</label>
        <select name="department_id" class="form-select" required>
            <option value="">-- Select Department --</option>
            <?php foreach($departments as $d): ?>
            <option value="<?= $d['department_id'] ?>" <?= ($student['program_id'] ?? '') == $d['department_id'] ? 'selected' : '' ?>>
                <?= $d['department_name'] ?> (<?= $d['department_code'] ?? 'N/A' ?>)
            </option>
            <?php endforeach; ?>
        </select>
    </div>
    
    <div class="col-md-6">
        <label class="form-label">Status</label>
        <select name="status" class="form-select">
            <option value="active" <?= ($student['status'] ?? '') == 'active' ? 'selected' : '' ?>>Active</option>
            <option value="inactive" <?= ($student['status'] ?? '') == 'inactive' ? 'selected' : '' ?>>Inactive</option>
            <option value="graduated" <?= ($student['status'] ?? '') == 'graduated' ? 'selected' : '' ?>>Graduated</option>
            <option value="suspended" <?= ($student['status'] ?? '') == 'suspended' ? 'selected' : '' ?>>Suspended</option>
        </select>
    </div>
    
    <div class="col-12">
        <button type="submit" class="btn btn-primary">Update Student</button>
        <a href="view.php?id=<?= $id ?>" class="btn btn-secondary">Cancel</a>
    </div>
</form>
<?php include '../../includes/footer.php'; ?>