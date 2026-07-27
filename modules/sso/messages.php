<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
requireSSO();

$conn = getConnection();
$message = '';
$error = '';

$studentRoleId = (int) mysqli_fetch_column(mysqli_query($conn, "SELECT role_id FROM roles WHERE role_name = 'Student' LIMIT 1"));
$teacherRoleId = (int) mysqli_fetch_column(mysqli_query($conn, "SELECT role_id FROM roles WHERE role_name = 'Teacher' LIMIT 1"));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title   = trim((string) ($_POST['title'] ?? ''));
    $body    = trim((string) ($_POST['body'] ?? ''));
    $target  = (string) ($_POST['target'] ?? 'students');

    if ($title === '' || $body === '') {
        $error = 'Title and message body are required.';
    } else {
        $targetRoleId = $target === 'teachers' ? $teacherRoleId : $studentRoleId;
        $senderId = (int) $_SESSION['user_id'];

        $stmt = mysqli_prepare($conn, 'SELECT user_id FROM users WHERE role_id = ?');
        mysqli_stmt_bind_param($stmt, 'i', $targetRoleId);
        mysqli_stmt_execute($stmt);
        $recipients = mysqli_fetch_all(mysqli_stmt_get_result($stmt), MYSQLI_ASSOC);
        mysqli_stmt_close($stmt);

        $ins = mysqli_prepare($conn, 'INSERT INTO lms_notifications (recipient_user_id, sender_user_id, category, title, body) VALUES (?, ?, ?, ?, ?)');
        $cat = 'message';
        $count = 0;
        foreach ($recipients as $r) {
            $rid = (int) $r['user_id'];
            mysqli_stmt_bind_param($ins, 'iisss', $rid, $senderId, $cat, $title, $body);
            mysqli_stmt_execute($ins);
            $count++;
        }
        mysqli_stmt_close($ins);
        $label = $target === 'teachers' ? 'teachers' : 'students';
        $message = "Message sent to {$count} {$label}.";
    }
}

$senderId = (int) $_SESSION['user_id'];

$recentStmt = mysqli_prepare($conn,
    "SELECT n.title, n.body, n.created_at,
            (SELECT COUNT(*) FROM lms_notifications n2
             WHERE n2.sender_user_id = n.sender_user_id
               AND n2.title = n.title AND n2.body = n.body
               AND n2.category = 'message') AS recipient_count,
            CASE
                WHEN EXISTS (SELECT 1 FROM users u WHERE u.user_id = n.recipient_user_id AND u.role_id = ?) THEN 'Teachers'
                ELSE 'Students'
            END AS target_group
     FROM lms_notifications n
     WHERE n.sender_user_id = ? AND n.category = 'message'
     GROUP BY n.title, n.body, n.created_at, n.sender_user_id
     ORDER BY n.created_at DESC LIMIT 10"
);
mysqli_stmt_bind_param($recentStmt, 'ii', $teacherRoleId, $senderId);
mysqli_stmt_execute($recentStmt);
$recent = mysqli_fetch_all(mysqli_stmt_get_result($recentStmt), MYSQLI_ASSOC);
mysqli_stmt_close($recentStmt);

$sid = (int) $_SESSION['user_id'];
$totalSent = (int) mysqli_fetch_column(mysqli_query($conn, "SELECT COUNT(*) FROM lms_notifications WHERE sender_user_id = {$sid} AND category = 'message'"));
$unreadStudent = (int) mysqli_fetch_column(mysqli_query($conn, "SELECT COUNT(*) FROM lms_notifications n JOIN users u ON u.user_id = n.recipient_user_id WHERE n.sender_user_id = {$sid} AND n.category = 'message' AND n.is_read = 0 AND u.role_id = {$studentRoleId}"));
$unreadTeacher = (int) mysqli_fetch_column(mysqli_query($conn, "SELECT COUNT(*) FROM lms_notifications n JOIN users u ON u.user_id = n.recipient_user_id WHERE n.sender_user_id = {$sid} AND n.category = 'message' AND n.is_read = 0 AND u.role_id = {$teacherRoleId}"));
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Messages | SSO Admin</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= BASE_URL ?>modules/lms/public/assets/style.css?v=<?= filemtime(__DIR__ . '/../lms/public/assets/style.css') ?>">
</head>
<body>
<div class="app-shell">
    <aside class="sidebar">
        <a href="<?= BASE_URL ?>" class="brand">
            <div class="brand-mark">SSO</div>
            <div>
                <h1>SSO Admin</h1>
                <p>System Administration</p>
            </div>
        </a>
        <nav class="nav">
            <span class="nav-section-label">Communication</span>
            <a class="active" href="<?= BASE_URL ?>modules/sso/messages.php">
                <span class="nav-icon">&#128172;</span> Messages
            </a>
            <span class="nav-section-label">Account</span>
            <a href="<?= BASE_URL ?>modules/sso/logout.php">
                <span class="nav-icon">&#10140;</span> Logout
            </a>
        </nav>
    </aside>

    <main class="content">
        <div class="topbar">
            <div>
                <span class="eyebrow">SSO Admin</span>
                <h2>Messages</h2>
            </div>
            <div class="topbar-actions">
                <div class="topbar-user-dropdown">
                    <button class="topbar-user-btn">
                        <span class="topbar-user-avatar"><?= strtoupper(substr($_SESSION['full_name'] ?? 'A', 0, 1)) ?></span>
                        <span class="topbar-user-name"><?= htmlspecialchars($_SESSION['full_name'] ?? 'Admin') ?></span>
                        <span class="topbar-chevron">&#9662;</span>
                    </button>
                    <div class="topbar-dropdown-menu">
                        <a href="<?= BASE_URL ?>modules/sso/logout.php">&#x2192; Logout</a>
                    </div>
                </div>
            </div>
        </div>

        <div class="grid-2">
            <div>
                <div class="card">
                    <div class="card-header">
                        <h3>Send Message</h3>
                        <p class="muted">Choose recipients and compose your message.</p>
                    </div>
                    <?php if ($message): ?><div class="alert alert-success"><?= htmlspecialchars($message) ?></div><?php endif; ?>
                    <?php if ($error): ?><div class="alert alert-error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
                    <form method="post" id="msg-form">
                        <div class="login-tabs">
                            <button type="button" class="login-tab active" onclick="setTarget('students', this)">&#127891; Students</button>
                            <button type="button" class="login-tab" onclick="setTarget('teachers', this)">&#128104;&#8205;&#127979; Teachers</button>
                        </div>
                        <input type="hidden" name="target" id="msg-target" value="students">
                        <div class="field">
                            <label>Title</label>
                            <input type="text" name="title" required placeholder="e.g. Campus Notice, Schedule Update">
                        </div>
                        <div class="field">
                            <label>Message</label>
                            <textarea name="body" required rows="6" placeholder="Write your message here..."></textarea>
                        </div>
                        <button class="btn btn-primary" type="submit">Send Message</button>
                    </form>
                </div>
            </div>

            <div>
                <div class="card" style="margin-bottom:16px;">
                    <div class="card-header"><h3>Overview</h3></div>
                    <div class="stat-row" style="grid-template-columns:repeat(3,1fr);margin-bottom:0;">
                        <div class="stat-card-v2">
                            <div class="stat-label">Total Sent</div>
                            <div class="stat-number"><?= $totalSent ?></div>
                        </div>
                        <div class="stat-card-v2">
                            <div class="stat-label">Unread (Students)</div>
                            <div class="stat-number" style="color:var(--warning);"><?= $unreadStudent ?></div>
                        </div>
                        <div class="stat-card-v2">
                            <div class="stat-label">Unread (Teachers)</div>
                            <div class="stat-number" style="color:var(--warning);"><?= $unreadTeacher ?></div>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header"><h3>Recent Messages</h3></div>
                    <?php if (empty($recent)): ?>
                        <p class="muted" style="padding:16px 0;">No messages sent yet.</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table>
                                <thead>
                                    <tr><th>Title</th><th>To</th><th>Sent</th></tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recent as $r): ?>
                                        <tr>
                                            <td style="font-weight:600;"><?= htmlspecialchars($r['title']) ?></td>
                                            <td>
                                                <span class="badge badge-outline"><?= htmlspecialchars($r['target_group'] ?? 'Students') ?></span>
                                                <span class="muted" style="margin-left:4px;">(<?= (int) $r['recipient_count'] ?>)</span>
                                            </td>
                                            <td class="muted"><?= date('M j, g:i A', strtotime($r['created_at'])) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>
</div>
<script>
function setTarget(target, btn) {
    document.querySelectorAll('.login-tab').forEach(t => t.classList.remove('active'));
    btn.classList.add('active');
    document.getElementById('msg-target').value = target;
}
</script>
</body>
</html>
