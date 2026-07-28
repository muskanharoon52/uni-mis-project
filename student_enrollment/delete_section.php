<?php
require_once __DIR__ . '/../config/db_connect.php';
require_once __DIR__ . '/../modules/sso/includes/auth.php';
requireLogin();

$conn = getConnection();
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

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm'])) {
    $delete_sql = "DELETE FROM sections WHERE section_id = ?";
    $delete_stmt = $conn->prepare($delete_sql);
    $delete_stmt->bind_param("i", $id);
    if ($delete_stmt->execute()) { header("Location: index.php?success=Section deleted successfully!"); exit; }
    else { $error = "Error deleting section: " . $conn->error; }
    $delete_stmt->close();
}

require_once __DIR__ . '/../includes/header.php';
$page_title = 'Delete Section';
include __DIR__ . '/../includes/sidebar.php';
?>

<div class="page-header">
    <h4><i class="fas fa-trash-alt" style="color:var(--danger);"></i> Delete Section</h4>
    <div class="page-header-actions">
        <a href="index.php" class="btn btn-outline"><i class="fas fa-arrow-left"></i> Back to List</a>
    </div>
</div>

<?php if (isset($error)): ?><div class="alert alert-error"><?= htmlspecialchars($error) ?></div><?php endif; ?>

<div class="card" style="max-width:560px;margin:0 auto;">
    <div class="card-content" style="padding:32px;text-align:center;">
        <div style="width:64px;height:64px;border-radius:50%;background:var(--danger-bg);display:flex;align-items:center;justify-content:center;margin:0 auto 16px;">
            <i class="fas fa-exclamation-triangle" style="font-size:28px;color:var(--danger);"></i>
        </div>
        <h5 style="margin-bottom:4px;">Are you sure you want to delete this section?</h5>
        <p style="color:var(--muted);margin-bottom:20px;">This action cannot be undone.</p>

        <div style="text-align:left;background:var(--bg);border:1px solid var(--border);border-radius:var(--radius);padding:16px;margin-bottom:24px;">
            <div class="detail-row">
                <div class="detail-label">Section</div>
                <div class="detail-value"><strong><?= htmlspecialchars($section['section_name']) ?></strong></div>
            </div>
            <div class="detail-row">
                <div class="detail-label">Semester</div>
                <div class="detail-value"><?= htmlspecialchars($section['semester_id']) ?></div>
            </div>
            <div class="detail-row">
                <div class="detail-label">Status</div>
                <div class="detail-value"><span class="status-badge <?= $section['status'] ?>"><?= $section['status'] ?></span></div>
            </div>
        </div>

        <form method="POST" action="">
            <div style="display:flex;gap:12px;justify-content:center;">
                <button type="submit" name="confirm" class="btn btn-danger"><i class="fas fa-trash-alt"></i> Yes, Delete</button>
                <a href="index.php" class="btn btn-outline"><i class="fas fa-times"></i> Cancel</a>
            </div>
        </form>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
