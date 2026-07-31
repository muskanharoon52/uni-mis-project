<?php
$ssrActive = 'submit';
$pageTitle = 'Submit Schedule Request';
require_once __DIR__ . '/_common.php';

$search = isset($_GET['student']) ? trim($_GET['student']) : '';
$student = null;
$tt_entries = [];

if (!empty($search)) {
    $student = ssr_find_student($search);
    if (!$student) {
        $error = "No registered student found for the given ID. Try a roll number, student ID or application ID.";
    } else {
        $tt_entries = ssr_student_timetable($student);
    }
}

// =============================================
// POST: submit request
// =============================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $student_id = (int)($_POST['student_id'] ?? 0);
    $course_id = (int)($_POST['course_id'] ?? 0);
    $conflict_type = trim($_POST['conflict_type'] ?? '');
    $description = trim($_POST['conflict_description'] ?? '');
    $solution = trim($_POST['requested_solution'] ?? '');

    $student = ssr_student_row($student_id);
    if (!$student) {
        $error = "Student not found.";
    } elseif ($course_id <= 0) {
        $error = "Please select a course from the student's timetable.";
    } elseif ($conflict_type === '') {
        $error = "Please select a conflict type.";
    } elseif ($description === '') {
        $error = "Please describe the conflict/schedule issue.";
    } else {
        $current_tt = '';
        $tt = ssr_student_timetable($student);
        foreach ($tt as $e) {
            $current_tt .= date('h:i A', strtotime($e['start_time'])) . '-' . date('h:i A', strtotime($e['end_time'])) . ' ' . $e['day_of_week'] . ' ' . $e['course_code'] . ' (' . ($e['room_no'] ?: 'TBA') . "); ";
        }
        $current_tt = rtrim($current_tt, '; ');
        if (ssr_log_request($student, $course_id, $conflict_type, $description, $solution, $current_tt)) {
            $success = "Schedule request submitted for <strong>" . htmlspecialchars($student['full_name']) . "</strong>. A coordinator will review it shortly.";
            $student = null;
            $tt_entries = [];
            $search = '';
        } else {
            $error = "Failed to save the request. Please try again.";
        }
    }
}

include __DIR__ . '/../includes/header.php';
?>

    <div class="container-fluid">
        <div class="page-header">
            <h2><i class="fas fa-calendar-alt"></i> Submit Schedule Request</h2>
            <p class="text-muted mb-0">Log a schedule conflict or timing request against a student's published timetable.</p>
        </div>

        <?php include __DIR__ . '/_subnav.php'; ?>

        <?php if ($error): ?><div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> <?= $error ?></div><?php endif; ?>
        <?php if ($success): ?><div class="alert alert-success"><i class="fas fa-check-circle"></i> <?= $success ?></div><?php endif; ?>

        <!-- Step 1: Find student -->
        <div class="card">
            <div class="card-header"><h5><i class="fas fa-user-graduate"></i> Step 1 - Find Student</h5></div>
            <div class="card-body">
                <form method="GET" class="row g-2 align-items-end">
                    <div class="col-md-5">
                        <label class="form-label small text-muted fw-semibold">Student ID / Roll No / Application ID</label>
                        <input type="text" name="student" class="form-control" value="<?= htmlspecialchars($search); ?>" placeholder="e.g. 2026-5-044 or 44 or 910">
                    </div>
                    <div class="col-auto">
                        <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> Search</button>
                    </div>
                </form>
            </div>
        </div>

        <?php if ($student): ?>
            <!-- Student summary -->
            <div class="card mt-3">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="fas fa-id-card"></i> <?= htmlspecialchars($student['full_name']); ?></h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3"><small class="text-muted">Roll No</small><br><strong><?= htmlspecialchars($student['roll_no'] ?? '—'); ?></strong></div>
                        <div class="col-md-3"><small class="text-muted">Program</small><br><strong><?= htmlspecialchars($student['program_name'] ?? '—'); ?></strong></div>
                        <div class="col-md-2"><small class="text-muted">Semester</small><br><strong><?= htmlspecialchars($student['semester_name'] ?? '—'); ?></strong></div>
                        <div class="col-md-2"><small class="text-muted">Section</small><br><strong><?= htmlspecialchars($student['section_name'] ?? '—'); ?></strong></div>
                        <div class="col-md-2"><small class="text-muted">Student ID</small><br><strong><?= (int)$student['student_id']; ?></strong></div>
                    </div>
                </div>
            </div>

            <!-- Step 2: published timetable -->
            <div class="card mt-3">
                <div class="card-header"><h5><i class="fas fa-table"></i> Step 2 - Current Published Timetable</h5></div>
                <div class="card-body">
                    <?php if (!empty($tt_entries)): ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Day</th>
                                        <th>Time</th>
                                        <th>Course</th>
                                        <th>Room</th>
                                        <th>Teacher</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($tt_entries as $e): ?>
                                        <tr>
                                            <td><span class="badge bg-secondary"><?= htmlspecialchars($e['day_of_week']); ?></span></td>
                                            <td><?= date('h:i A', strtotime($e['start_time'])); ?> - <?= date('h:i A', strtotime($e['end_time'])); ?></td>
                                            <td><strong><?= htmlspecialchars($e['course_code']); ?></strong> - <?= htmlspecialchars($e['course_name']); ?></td>
                                            <td><?= htmlspecialchars($e['room_no'] ?? 'TBA'); ?></td>
                                            <td><?= htmlspecialchars($e['teacher_name'] ?? '—'); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="empty-state">
                            <i class="fas fa-exclamation-triangle"></i>
                            <h5>No Published Timetable</h5>
                            <p class="text-muted">This student has no published timetable for their current session/semester. Requests can still be logged below.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Step 3: request form -->
            <div class="card mt-3">
                <div class="card-header"><h5><i class="fas fa-edit"></i> Step 3 - Request Details</h5></div>
                <div class="card-body">
                    <form method="POST">
                        <input type="hidden" name="student_id" value="<?= (int)$student['student_id']; ?>">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label small text-muted fw-semibold">Affected Course <span class="text-danger">*</span></label>
                                <select name="course_id" class="form-select" required>
                                    <option value="">-- Select Course --</option>
                                    <?php foreach ($tt_entries as $e): ?>
                                        <option value="<?= (int)$e['course_id']; ?>"><?= htmlspecialchars($e['course_code']); ?> - <?= htmlspecialchars($e['course_name']); ?> (<?= htmlspecialchars($e['day_of_week']); ?> <?= date('h:i A', strtotime($e['start_time'])); ?>)</option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small text-muted fw-semibold">Conflict / Request Type <span class="text-danger">*</span></label>
                                <select name="conflict_type" class="form-select" required>
                                    <option value="">-- Select Type --</option>
                                    <option value="Time Conflict">Time Conflict</option>
                                    <option value="Room Conflict">Room Conflict</option>
                                    <option value="Teacher Conflict">Teacher Conflict</option>
                                    <option value="Overlapping Classes">Overlapping Classes</option>
                                    <option value="Religious Obligation">Religious Obligation</option>
                                    <option value="Part-time Job">Part-time Job</option>
                                    <option value="Health / Personal">Health / Personal</option>
                                    <option value="Other">Other</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small text-muted fw-semibold">Conflict Description <span class="text-danger">*</span></label>
                                <textarea name="conflict_description" class="form-control" rows="3" required placeholder="Describe the schedule conflict or the reason for the request..."></textarea>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small text-muted fw-semibold">Requested Solution</label>
                                <textarea name="requested_solution" class="form-control" rows="3" placeholder="e.g. Move class to Tuesday 11:00 AM, or switch section B..."></textarea>
                            </div>
                            <div class="col-12">
                                <button type="submit" class="btn btn-primary"><i class="fas fa-paper-plane"></i> Submit Request</button>
                                <a href="submit.php" class="btn btn-outline-secondary"><i class="fas fa-redo"></i> Reset</a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        <?php endif; ?>
    </div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
