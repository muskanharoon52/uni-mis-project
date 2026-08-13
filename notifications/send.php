<?php
$pageTitle = 'Send Notification';
require_once __DIR__ . '/../config/db_connect.php';
require_once __DIR__ . '/../modules/sso/includes/auth.php';

if (!isLoggedIn()) {
    header('Location: /uni-mis-project/');
    exit;
}
require_once __DIR__ . '/../includes/activity.php';
require_once __DIR__ . '/_helpers.php';
global $conn;

$error = '';
$success = '';
$form = [
    'title' => '',
    'message' => '',
    'audience' => 'Both',
    'faculty_scope' => 'All',
    'faculty_depts' => [],
    'faculty_batches' => [],
    'student_scope' => 'All',
    'student_depts' => [],
    'student_batches' => [],
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $form['title'] = trim($_POST['title'] ?? '');
    $form['message'] = trim($_POST['message'] ?? '');
    $form['audience'] = in_array($_POST['audience'] ?? '', ['Faculty', 'Students', 'Both'], true) ? $_POST['audience'] : 'Both';
    $form['faculty_scope'] = ($_POST['faculty_scope'] ?? '') === 'Selected' ? 'Selected' : 'All';
    $form['student_scope'] = ($_POST['student_scope'] ?? '') === 'Selected' ? 'Selected' : 'All';
    $form['faculty_depts'] = isset($_POST['faculty_depts']) ? array_map('intval', (array)$_POST['faculty_depts']) : [];
    $form['faculty_batches'] = isset($_POST['faculty_batches']) ? array_map('intval', (array)$_POST['faculty_batches']) : [];
    $form['student_depts'] = isset($_POST['student_depts']) ? array_map('intval', (array)$_POST['student_depts']) : [];
    $form['student_batches'] = isset($_POST['student_batches']) ? array_map('intval', (array)$_POST['student_batches']) : [];

    if ($form['title'] === '') {
        $error = 'Notification title is required.';
    } elseif (strlen($form['title']) > 255) {
        $error = 'Title is too long (max 255 characters).';
    } elseif ($form['message'] === '') {
        $error = 'Notification message is required.';
    } else {
        $result = notif_compute(
            $conn,
            $form['audience'],
            $form['faculty_scope'],
            $form['faculty_depts'],
            $form['faculty_batches'],
            $form['student_scope'],
            $form['student_depts'],
            $form['student_batches']
        );

        if ($result['total'] === 0) {
            $error = 'No recipients match the selected audience. Please adjust the filters.';
        } else {
            mysqli_begin_transaction($conn);
            try {
                $stmt = mysqli_prepare($conn, "INSERT INTO notifications (title, message, audience_type, sent_by, recipient_count) VALUES (?, ?, ?, ?, ?)");
                mysqli_stmt_bind_param($stmt, 'sssii', $form['title'], $form['message'], $form['audience'], $_SESSION['user_id'], $result['total']);
                mysqli_stmt_execute($stmt);
                $notificationId = mysqli_insert_id($conn);
                mysqli_stmt_close($stmt);

                $stmtR = mysqli_prepare($conn, "INSERT INTO notification_recipients (notification_id, recipient_type, recipient_id) VALUES (?, ?, ?)");
                foreach ($result['faculty'] as $fid) {
                    $rtype = 'Faculty';
                    mysqli_stmt_bind_param($stmtR, 'isi', $notificationId, $rtype, $fid);
                    mysqli_stmt_execute($stmtR);
                }
                foreach ($result['students'] as $sid) {
                    $rtype = 'Students';
                    mysqli_stmt_bind_param($stmtR, 'isi', $notificationId, $rtype, $sid);
                    mysqli_stmt_execute($stmtR);
                }
                mysqli_stmt_close($stmtR);

                // Mirror into lms_notifications so the message also appears in the
                // teacher/student LMS Messages page (keyed by users.user_id).
                $lmsIns = mysqli_prepare($conn, "INSERT INTO lms_notifications (recipient_user_id, sender_user_id, category, title, body) VALUES (?, ?, 'message', ?, ?)");
                $senderUid = (int) $_SESSION['user_id'];
                $lmsDelivered = 0;
                if ($lmsIns) {
                    foreach ($result['faculty'] as $fid) {
                        $tq = mysqli_query($conn, "SELECT user_id FROM teachers WHERE teacher_id = " . (int)$fid . " AND user_id IS NOT NULL AND user_id > 0 LIMIT 1");
                        if ($tq && $row = mysqli_fetch_assoc($tq)) {
                            mysqli_stmt_bind_param($lmsIns, 'iiss', $row['user_id'], $senderUid, $form['title'], $form['message']);
                            mysqli_stmt_execute($lmsIns);
                            $lmsDelivered++;
                        }
                    }
                    foreach ($result['students'] as $sid) {
                        $sq = mysqli_query($conn, "SELECT user_id FROM students WHERE student_id = " . (int)$sid . " AND user_id IS NOT NULL AND user_id > 0 LIMIT 1");
                        if ($sq && $row = mysqli_fetch_assoc($sq)) {
                            mysqli_stmt_bind_param($lmsIns, 'iiss', $row['user_id'], $senderUid, $form['title'], $form['message']);
                            mysqli_stmt_execute($lmsIns);
                            $lmsDelivered++;
                        }
                    }
                    mysqli_stmt_close($lmsIns);
                }

                mysqli_commit($conn);

                log_activity('Notifications', 'Notification Sent', 'notifications', $notificationId,
                    'Title: ' . mb_substr($form['title'], 0, 80)
                    . ' | Audience: ' . $form['audience']
                    . ' | Faculty: ' . $result['faculty_count']
                    . ' | Students: ' . $result['student_count']);

                $success = 'Notification sent to ' . $result['total'] . ' recipient(s) (' . $result['faculty_count'] . ' faculty, ' . $result['student_count'] . ' students). Delivered to ' . $lmsDelivered . ' LMS account(s).';
                $form = [
                    'title' => '', 'message' => '', 'audience' => 'Both',
                    'faculty_scope' => 'All', 'faculty_depts' => [], 'faculty_batches' => [],
                    'student_scope' => 'All', 'student_depts' => [], 'student_batches' => [],
                ];
            } catch (Exception $e) {
                mysqli_rollback($conn);
                $error = 'Failed to send notification: ' . $e->getMessage();
            }
        }
    }
}

$departments = notif_departments($conn);
$batches = notif_batches($conn);
include __DIR__ . '/../includes/header.php';
?>

    <div class="container-fluid">
        <div class="page-header d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div>
                <h2><i class="fas fa-bell"></i> Send Notification</h2>
                <span class="text-muted small">Share announcements with faculty and/or students.</span>
            </div>
            <a href="index.php" class="btn btn-outline-secondary btn-sm"><i class="fas fa-history"></i> Sent Notifications</a>
        </div>

        <?php if ($error): ?><div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?></div><?php endif; ?>
        <?php if ($success): ?><div class="alert alert-success"><i class="fas fa-check-circle"></i> <?= htmlspecialchars($success) ?></div><?php endif; ?>

        <form method="POST" action="send.php" id="notifForm">
            <div class="panel">
                <h5 class="mb-3">Notification Details</h5>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Title <span class="text-danger">*</span></label>
                        <input type="text" name="title" class="form-control" maxlength="255" value="<?= htmlspecialchars($form['title']) ?>" placeholder="e.g. Midterm Exam Schedule">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Audience</label>
                        <input type="hidden" name="audience" id="audienceInput" value="<?= htmlspecialchars($form['audience']) ?>">
                        <div class="dropdown">
                            <button class="btn btn-light border dropdown-toggle w-100 text-start" type="button" id="audienceDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                                <span id="audienceLabel">
                                    <i class="fas fa-users"></i> Select Audience...
                                </span>
                            </button>
                            <ul class="dropdown-menu w-100" aria-labelledby="audienceDropdown">
                                <li><a class="dropdown-item" href="#" data-value="Faculty"><i class="fas fa-chalkboard-teacher"></i> Faculty</a></li>
                                <li><a class="dropdown-item" href="#" data-value="Students"><i class="fas fa-user-graduate"></i> Students</a></li>
                                <li><a class="dropdown-item" href="#" data-value="Both"><i class="fas fa-users"></i> Both Faculty &amp; Students</a></li>
                            </ul>
                        </div>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Message <span class="text-danger">*</span></label>
                        <textarea name="message" class="form-control" rows="4" maxlength="5000" placeholder="Write the notification message..."><?= htmlspecialchars($form['message']) ?></textarea>
                    </div>
                </div>
            </div>

            <!-- Faculty audience -->
            <div class="panel" id="facultyPanel">
                <h5 class="mb-3"><i class="fas fa-chalkboard-teacher"></i> Faculty Recipients</h5>
                <div class="btn-group w-100 mb-3 notif-scope">
                    <label class="btn btn-outline-primary">
                        <input type="radio" name="faculty_scope" class="btn-check" value="All" <?= $form['faculty_scope'] === 'All' ? 'checked' : '' ?>>
                        <i class="fas fa-users"></i> All Faculty
                    </label>
                    <label class="btn btn-outline-primary">
                        <input type="radio" name="faculty_scope" class="btn-check" value="Selected" <?= $form['faculty_scope'] === 'Selected' ? 'checked' : '' ?>>
                        <i class="fas fa-filter"></i> By Department &amp; Batch
                    </label>
                </div>
                <div id="facultyFilters" class="row g-3" style="<?= $form['faculty_scope'] === 'All' ? 'display:none;' : '' ?>">
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold text-muted">Departments</label>
                        <div class="notif-checkbox-grid">
                            <?php foreach ($departments as $d): ?>
                                <label class="form-check">
                                    <input type="checkbox" name="faculty_depts[]" class="form-check-input notif-fdept" value="<?= (int)$d['department_id']; ?>" <?= in_array((int)$d['department_id'], $form['faculty_depts'], true) ? 'checked' : '' ?>>
                                    <span class="form-check-label"><?= htmlspecialchars($d['department_name']); ?></span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold text-muted">Batches (Year)</label>
                        <div class="notif-checkbox-grid">
                            <?php foreach ($batches as $b): ?>
                                <label class="form-check">
                                    <input type="checkbox" name="faculty_batches[]" class="form-check-input notif-fbatch" value="<?= (int)$b; ?>" <?= in_array((int)$b, $form['faculty_batches'], true) ? 'checked' : '' ?>>
                                    <span class="form-check-label">Batch <?= (int)$b; ?></span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Student audience -->
            <div class="panel" id="studentPanel">
                <h5 class="mb-3"><i class="fas fa-user-graduate"></i> Student Recipients</h5>
                <div class="btn-group w-100 mb-3 notif-scope">
                    <label class="btn btn-outline-primary">
                        <input type="radio" name="student_scope" class="btn-check" value="All" <?= $form['student_scope'] === 'All' ? 'checked' : '' ?>>
                        <i class="fas fa-users"></i> All Enrolled Students
                    </label>
                    <label class="btn btn-outline-primary">
                        <input type="radio" name="student_scope" class="btn-check" value="Selected" <?= $form['student_scope'] === 'Selected' ? 'checked' : '' ?>>
                        <i class="fas fa-filter"></i> By Department &amp; Batch
                    </label>
                </div>
                <div id="studentFilters" class="row g-3" style="<?= $form['student_scope'] === 'All' ? 'display:none;' : '' ?>">
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold text-muted">Departments</label>
                        <div class="notif-checkbox-grid">
                            <?php foreach ($departments as $d): ?>
                                <label class="form-check">
                                    <input type="checkbox" name="student_depts[]" class="form-check-input notif-sdept" value="<?= (int)$d['department_id']; ?>" <?= in_array((int)$d['department_id'], $form['student_depts'], true) ? 'checked' : '' ?>>
                                    <span class="form-check-label"><?= htmlspecialchars($d['department_name']); ?></span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold text-muted">Batches (Year)</label>
                        <div class="notif-checkbox-grid">
                            <?php foreach ($batches as $b): ?>
                                <label class="form-check">
                                    <input type="checkbox" name="student_batches[]" class="form-check-input notif-sbatch" value="<?= (int)$b; ?>" <?= in_array((int)$b, $form['student_batches'], true) ? 'checked' : '' ?>>
                                    <span class="form-check-label">Batch <?= (int)$b; ?></span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>

            <div class="panel d-flex align-items-center justify-content-between flex-wrap gap-2">
                <div>
                    <strong>Live recipient count:</strong>
                    <span id="recipientCount" class="badge bg-primary fs-6">—</span>
                    <small class="text-muted d-block">Faculty / Students update automatically as you change the selection.</small>
                </div>
                <button type="submit" class="btn btn-primary"><i class="fas fa-paper-plane"></i> Send Notification</button>
            </div>
        </form>
    </div>

    <style>
    .notif-checkbox-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(170px, 1fr));
        gap: 4px 12px;
        max-height: 220px;
        overflow-y: auto;
        padding: 8px;
        border: 1px solid #eef2f6;
        border-radius: 8px;
        background: #fff;
    }
    .notif-checkbox-grid .form-check { margin-bottom: 2px; }
    </style>

    <script>
    (function () {
        var form = document.getElementById('notifForm');
        var audienceInput = document.getElementById('audienceInput');
        var audienceLabel = document.getElementById('audienceLabel');
        var audienceMap = {
            'Faculty': '<i class="fas fa-chalkboard-teacher"></i> Faculty',
            'Students': '<i class="fas fa-user-graduate"></i> Students',
            'Both': '<i class="fas fa-users"></i> Both Faculty &amp; Students'
        };

        function visibleAudiences() {
            var a = audienceInput.value || 'Both';
            var wantF = a === 'Faculty' || a === 'Both';
            var wantS = a === 'Students' || a === 'Both';
            document.getElementById('facultyPanel').style.display = wantF ? '' : 'none';
            document.getElementById('studentPanel').style.display = wantS ? '' : 'none';
            return a;
        }

        function refresh() {
            var audience = visibleAudiences();
            var params = new URLSearchParams();
            params.set('audience', audience);
            params.set('faculty_scope', form.querySelector('input[name="faculty_scope"]:checked').value);
            params.set('student_scope', form.querySelector('input[name="student_scope"]:checked').value);
            ['faculty_depts', 'faculty_batches', 'student_depts', 'student_batches'].forEach(function (name) {
                var checked = Array.from(form.querySelectorAll('input[name="' + name + '"]:checked')).map(function (c) { return c.value; });
                checked.forEach(function (v) { params.append(name, v); });
            });
            document.getElementById('facultyFilters').style.display = params.get('faculty_scope') === 'Selected' ? '' : 'none';
            document.getElementById('studentFilters').style.display = params.get('student_scope') === 'Selected' ? '' : 'none';

            fetch('preview.php?' + params.toString(), { headers: { 'Accept': 'application/json' } })
                .then(function (r) { return r.json(); })
                .then(function (d) {
                    if (d.total !== undefined) {
                        document.getElementById('recipientCount').textContent =
                            d.total + ' total (' + d.faculty_count + ' faculty, ' + d.student_count + ' students)';
                    }
                })
                .catch(function () {
                    document.getElementById('recipientCount').textContent = '—';
                });
        }

        document.querySelectorAll('#audienceDropdown + .dropdown-menu .dropdown-item').forEach(function (item) {
            item.addEventListener('click', function (e) {
                e.preventDefault();
                var value = item.getAttribute('data-value');
                audienceInput.value = value;
                audienceLabel.innerHTML = audienceMap[value] || '';
                refresh();
            });
        });

        form.addEventListener('change', refresh);
        if (audienceMap[audienceInput.value]) {
            audienceLabel.innerHTML = audienceMap[audienceInput.value];
        }
        refresh();
    })();
    </script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
