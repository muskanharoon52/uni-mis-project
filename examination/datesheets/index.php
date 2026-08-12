<?php

declare(strict_types=1);

$pageTitle = 'Datesheets';

require_once __DIR__ . '/../../config/db_connect.php';

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';

$uploadDir = $_SERVER['DOCUMENT_ROOT'] . '/uni-mis-project/uploads/datesheets/';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (($_POST['action'] ?? '') === 'upload') {
        $title        = trim((string) ($_POST['title'] ?? ''));
        $departmentId = !empty($_POST['department_id']) ? (int) $_POST['department_id'] : null;
        $semesterId   = !empty($_POST['semester_id']) ? (int) $_POST['semester_id'] : null;
        $examType     = !empty($_POST['exam_type']) ? (string) $_POST['exam_type'] : null;

        $errors = [];
        if ($title === '') {
            $errors[] = 'Title is required.';
        }
        if ($examType !== 'Mid' && $examType !== 'Final') {
            $errors[] = 'Please choose whether this datesheet is for Mid or Final exams.';
        }
        if (empty($_FILES['pdf_file']['name']) || $_FILES['pdf_file']['error'] !== UPLOAD_ERR_OK) {
            $errors[] = 'Please select a PDF file to upload.';
        } else {
            $fileInfo = pathinfo((string) $_FILES['pdf_file']['name']);
            $ext      = strtolower($fileInfo['extension'] ?? '');
            if ($ext !== 'pdf') {
                $errors[] = 'Only PDF files are allowed.';
            }
        }

        if (empty($errors)) {
            if (!is_dir($uploadDir)) {
                @mkdir($uploadDir, 0777, true);
            }
            $safeBase = preg_replace('/[^A-Za-z0-9_-]/', '-', $title);
            $fileName = date('Ymd_His') . '_' . substr($safeBase, 0, 60) . '.pdf';
            $target   = $uploadDir . $fileName;

            if (move_uploaded_file((string) $_FILES['pdf_file']['tmp_name'], $target)) {
                $stmt = $conn->prepare(
                    'INSERT INTO sbe_datesheets (title, department_id, semester_id, exam_type, file_name, file_path, uploaded_by)
                     VALUES (?, ?, ?, ?, ?, ?, ?)'
                );
                $userId   = (int) ($_SESSION['user_id'] ?? 0);
                $filePath = 'uploads/datesheets/' . $fileName;
                $stmt->bind_param('siisssi', $title, $departmentId, $semesterId, $examType, $fileName, $filePath, $userId);
                $stmt->execute();
                $stmt->close();

                $flash = 'Datesheet uploaded successfully.';
                $flashType = 'success';
            } else {
                $errors[] = 'Failed to move the uploaded file. Please try again.';
            }
        }
    } elseif (($_POST['action'] ?? '') === 'delete') {
        $datesheetId = (int) ($_POST['datesheet_id'] ?? 0);
        if ($datesheetId > 0) {
            $stmt = $conn->prepare('SELECT file_name FROM sbe_datesheets WHERE datesheet_id = ?');
            $stmt->bind_param('i', $datesheetId);
            $stmt->execute();
            $row = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if ($row) {
                $filePath = $uploadDir . $row['file_name'];
                if (is_file($filePath)) {
                    @unlink($filePath);
                }
                $del = $conn->prepare('DELETE FROM sbe_datesheets WHERE datesheet_id = ?');
                $del->bind_param('i', $datesheetId);
                $del->execute();
                $del->close();

                $flash = 'Datesheet deleted.';
                $flashType = 'success';
            }
        }
    }
}

$datesheets = $conn->query(
    'SELECT d.*, dept.department_name, sem.semester_name
     FROM sbe_datesheets d
     LEFT JOIN departments dept ON dept.department_id = d.department_id
     LEFT JOIN semesters sem ON sem.semester_id = d.semester_id
     ORDER BY d.created_at DESC'
)->fetch_all(MYSQLI_ASSOC);

$departments = $conn->query('SELECT department_id, department_name FROM departments ORDER BY department_name')->fetch_all(MYSQLI_ASSOC);
$semesters   = $conn->query('SELECT semester_id, semester_name FROM semesters ORDER BY semester_name')->fetch_all(MYSQLI_ASSOC);
?>

<div class="page">
    <div class="page-title-row">
        <div>
            <h1>Datesheets</h1>
            <p class="text-muted">Publish exam datesheets for Mid and Final exams. Teachers can view these from their SBE workspace.</p>
        </div>
    </div>

    <?php if (!empty($flash)): ?>
        <div class="alert alert-<?= $flashType === 'success' ? 'success' : 'danger' ?>"><?= htmlspecialchars($flash) ?></div>
    <?php endif; ?>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
            <?php foreach ($errors as $err): ?>
                <div><?= htmlspecialchars($err) ?></div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <div class="row g-4">
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header"><h5><i class="bi bi-upload me-2"></i>Upload New Datesheet</h5></div>
                <div class="card-body">
                    <form method="post" enctype="multipart/form-data">
                        <input type="hidden" name="action" value="upload">
                        <div class="mb-3">
                            <label class="form-label">Title *</label>
                            <input type="text" name="title" class="form-control" required maxlength="150"
                                   placeholder="e.g. Mid Term Exams 2026 (Spring)">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Exam Type *</label>
                            <select name="exam_type" class="form-select" required>
                                <option value="">-- Select --</option>
                                <option value="Mid">Mid</option>
                                <option value="Final">Final</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Department</label>
                            <select name="department_id" class="form-select">
                                <option value="">All Departments</option>
                                <?php foreach ($departments as $dept): ?>
                                    <option value="<?= (int) $dept['department_id'] ?>"><?= htmlspecialchars($dept['department_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Semester</label>
                            <select name="semester_id" class="form-select">
                                <option value="">All Semesters</option>
                                <?php foreach ($semesters as $sem): ?>
                                    <option value="<?= (int) $sem['semester_id'] ?>"><?= htmlspecialchars($sem['semester_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">PDF File *</label>
                            <input type="file" name="pdf_file" class="form-control" accept="application/pdf" required>
                            <div class="form-text">Only PDF files are accepted.</div>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="bi bi-cloud-upload me-1"></i> Upload Datesheet
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="card">
                <div class="card-header"><h5><i class="bi bi-file-earmark-pdf me-2"></i>Published Datesheets</h5></div>
                <div class="card-body p-0">
                    <?php if (empty($datesheets)): ?>
                        <div class="p-4 text-center text-muted">No datesheets uploaded yet.</div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead>
                                    <tr>
                                        <th>Title</th>
                                        <th>Type</th>
                                        <th>Department</th>
                                        <th>Semester</th>
                                        <th>Uploaded</th>
                                        <th class="text-end">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($datesheets as $ds): ?>
                                        <tr>
                                            <td>
                                                <a href="<?= BASE_URL ?><?= htmlspecialchars($ds['file_path']) ?>" target="_blank">
                                                    <i class="bi bi-file-earmark-pdf me-1 text-danger"></i><?= htmlspecialchars($ds['title']) ?>
                                                </a>
                                            </td>
                                            <td>
                                                <span class="badge <?= $ds['exam_type'] === 'Final' ? 'badge-grade-F' : 'badge-exam-mid' ?>">
                                                    <?= htmlspecialchars((string) $ds['exam_type']) ?>
                                                </span>
                                            </td>
                                            <td><?= $ds['department_name'] ? htmlspecialchars($ds['department_name']) : '<span class="text-muted">All</span>' ?></td>
                                            <td><?= $ds['semester_name'] ? htmlspecialchars($ds['semester_name']) : '<span class="text-muted">All</span>' ?></td>
                                            <td><?= htmlspecialchars(date('M d, Y h:i A', strtotime((string) $ds['created_at']))) ?></td>
                                            <td class="text-end">
                                                <a class="btn btn-sm btn-outline-primary" href="<?= BASE_URL ?><?= htmlspecialchars($ds['file_path']) ?>" target="_blank">
                                                    <i class="bi bi-eye"></i> View
                                                </a>
                                                <form method="post" class="d-inline" onsubmit="return confirm('Delete this datesheet?');">
                                                    <input type="hidden" name="action" value="delete">
                                                    <input type="hidden" name="datesheet_id" value="<?= (int) $ds['datesheet_id'] ?>">
                                                    <button type="submit" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
