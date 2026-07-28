<?php
require_once __DIR__ . '/../config/db_connect.php';
require_once __DIR__ . '/../modules/sso/includes/auth.php';
requireLogin();

$conn = getConnection();
$error = '';
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) { header("Location: index.php?error=Invalid section ID"); exit; }

$query = "SELECT * FROM sections WHERE section_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$section = $result->fetch_assoc();
$stmt->close();

if (!$section) { header("Location: index.php?error=Section not found"); exit; }

$semesters = $conn->query("SELECT semester_id, semester_name FROM semesters ORDER BY semester_name");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $section_name = trim($_POST['section_name'] ?? '');
    $semester_id = (int)$_POST['semester_id'] ?? 0;
    $capacity = (int)$_POST['capacity'] ?? 30;
    $status = $_POST['status'] ?? 'Active';

    if (empty($section_name)) $error = "Please enter section name";
    elseif ($semester_id <= 0) $error = "Please select a semester";

    if (empty($error)) {
        $update_query = "UPDATE sections SET section_name = ?, semester_id = ?, capacity = ?, status = ? WHERE section_id = ?";
        $update_stmt = $conn->prepare($update_query);
        $update_stmt->bind_param("sissi", $section_name, $semester_id, $capacity, $status, $id);
        if ($update_stmt->execute()) { header("Location: index.php?success=Section updated successfully!"); exit; }
        else { $error = "Error updating section: " . $conn->error; }
        $update_stmt->close();
    }
}

require_once __DIR__ . '/../includes/header.php';
$page_title = 'Edit Section';
include __DIR__ . '/../includes/sidebar.php';
?>

<div class="page-header">
    <h4><i class="fas fa-edit"></i> Edit Section</h4>
    <div class="page-header-actions">
        <a href="index.php" class="btn btn-outline"><i class="fas fa-arrow-left"></i> Back to List</a>
    </div>
</div>

<?php if ($error): ?><div class="alert alert-error"><?= htmlspecialchars($error) ?></div><?php endif; ?>

<div class="form-container">
    <form method="POST" action="">
        <div class="form-row">
            <div class="form-group">
                <label>Section Name <span class="required-star">*</span></label>
                <input type="text" name="section_name" value="<?= htmlspecialchars($section['section_name']) ?>" required>
            </div>
            <div class="form-group">
                <label>Semester <span class="required-star">*</span></label>
                <select name="semester_id" required>
                    <option value="">Select Semester</option>
                    <?php while($row = $semesters->fetch_assoc()): ?>
                        <option value="<?= $row['semester_id'] ?>" <?= $section['semester_id'] == $row['semester_id'] ? 'selected' : '' ?>><?= htmlspecialchars($row['semester_name']) ?></option>
                    <?php endwhile; ?>
                </select>
            </div>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label>Capacity</label>
                <input type="number" name="capacity" value="<?= $section['capacity'] ?>" min="1" max="100">
            </div>
            <div class="form-group">
                <label>Status</label>
                <select name="status">
                    <option value="Active" <?= $section['status'] == 'Active' ? 'selected' : '' ?>>Active</option>
                    <option value="Inactive" <?= $section['status'] == 'Inactive' ? 'selected' : '' ?>>Inactive</option>
                </select>
            </div>
        </div>
        <div class="form-actions">
            <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Update Section</button>
            <a href="index.php" class="btn btn-outline">Cancel</a>
        </div>
    </form>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
