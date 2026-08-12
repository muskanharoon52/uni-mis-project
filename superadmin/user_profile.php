<?php
$sa_page_title = 'User Profile';
define('SA_INIT', true);
require_once __DIR__ . '/includes/auth.php';
sa_guard();
$conn = getConnection();
$me = (int)($_SESSION['user_id'] ?? 0);

$tab = $_GET['tab'] ?? 'portal';
$tab = $tab === 'sbe' ? 'sbe' : 'portal';
$id = (int)($_GET['id'] ?? 0);

$msg = ''; $err = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'reset_password') {
        $newPass = (string)($_POST['new_password'] ?? '');
        if (strlen($newPass) < 4) {
            $err = 'Password must be at least 4 characters.';
        } elseif ($tab === 'portal' && $id > 0) {
            $hash = password_hash($newPass, PASSWORD_BCRYPT);
            $stmt = $conn->prepare("UPDATE users SET password_hash=? WHERE user_id=?");
            $stmt->bind_param('si', $hash, $id);
            if ($stmt->execute()) {
                $msg = "Password reset performed for user #$id (master override).";
                sa_log('RESET_PASSWORD', 'user', $id, "Master reset override for user #$id");
            } else $err = $conn->error;
        } elseif ($tab === 'sbe' && $id > 0) {
            $hash = password_hash($newPass, PASSWORD_BCRYPT);
            $stmt = $conn->prepare("UPDATE sbe_auth_users SET password_hash=? WHERE auth_id=?");
            $stmt->bind_param('si', $hash, $id);
            if ($stmt->execute()) {
                $msg = "Password reset performed for SBE account #$id (master override).";
                sa_log('RESET_PASSWORD', 'sbe_auth', $id, "Master reset override for SBE account #$id");
            } else $err = $conn->error;
        } else $err = 'Invalid target.';
    }

    if ($action === 'set_control') {
        $status = $_POST['status'] ?? 'Active';
        $reason = (string)($_POST['reason'] ?? '');
        if ($id > 0) {
            sa_apply_control($tab, $id, $status, $reason !== '' ? $reason : null, $me);
            $verb = ['Blocked' => 'BLOCK_USER', 'Frozen' => 'FREEZE_USER', 'Active' => 'UNBLOCK_USER'][$status] ?? 'CONTROL_USER';
            sa_log($verb, $tab === 'sbe' ? 'sbe_auth' : 'user', $id, "Account " . strtolower($status) . ($reason !== '' ? " | reason: $reason" : ''));
            $msg = "Account status set to $status.";
        }
    }

    if ($action === 'terminate_all') {
        $killed = sa_terminate_all_sessions($tab, $id);
        sa_log('TERMINATE_SESSIONS', $tab === 'sbe' ? 'sbe_auth' : 'user', $id, "Terminated $killed live session(s)");
        $msg = "Terminated $killed live session(s).";
    }
}

if ($tab === 'portal') {
    $u = mysqli_fetch_assoc(mysqli_query($conn, "SELECT u.*, r.role_name, d.department_name, COALESCE(u.department_id, t.department_id, p.department_id) dept_id, c.status ctl_status, c.reason ctl_reason
        FROM users u
        LEFT JOIN roles r ON r.role_id=u.role_id
        LEFT JOIN teachers t ON t.user_id=u.user_id
        LEFT JOIN students st ON st.user_id=u.user_id
        LEFT JOIN programs p ON p.program_id=st.program_id
        LEFT JOIN departments d ON d.department_id=COALESCE(u.department_id, t.department_id, p.department_id)
        LEFT JOIN sa_user_controls c ON c.user_type='portal' AND c.user_id=u.user_id
        WHERE u.user_id=$id LIMIT 1"));
    if (!$u) { $err = 'User not found.'; }
    $activity = $u ? mysqli_query($conn, "SELECT * FROM activity_logs WHERE performed_by=$id ORDER BY log_id DESC LIMIT 50") : null;
    $ctl = $u['ctl_status'] ?? 'Active';
    $isOnline = false;
    $sessions = [];
    foreach (sa_scan_sessions() as $s) {
        if ($s['user_type'] === 'portal' && (int)$s['user_id'] === $id) { $sessions[] = $s; $isOnline = true; }
    }
} else {
    $u = mysqli_fetch_assoc(mysqli_query($conn, "SELECT a.*, d.department_name, c.status ctl_status, c.reason ctl_reason
        FROM sbe_auth_users a
        LEFT JOIN teachers t ON t.teacher_id=a.teacher_id
        LEFT JOIN students st ON st.student_id=a.student_id
        LEFT JOIN programs p ON p.program_id=st.program_id
        LEFT JOIN departments d ON d.department_id=COALESCE(t.department_id, p.department_id)
        LEFT JOIN sa_user_controls c ON c.user_type='sbe' AND c.user_id=a.auth_id
        WHERE a.auth_id=$id LIMIT 1"));
    if (!$u) { $err = 'SBE account not found.'; }
    $activity = null;
    $ctl = $u['ctl_status'] ?? 'Active';
    $isOnline = false;
    $sessions = [];
    foreach (sa_scan_sessions() as $s) {
        if ($s['user_type'] === 'sbe' && (int)$s['sbe_auth_id'] === $id) { $sessions[] = $s; $isOnline = true; }
    }
}

$sa_active = 'users';
include __DIR__ . '/includes/header.php';
?>

<div class="flex jc-between" style="margin-bottom:4px">
  <div>
    <div class="page-title"><?= $u ? sa_es($u['full_name'] ?? $u['display_name'] ?? 'Account') : 'User Profile' ?></div>
    <div class="page-sub"><?= $tab === 'portal' ? 'Portal user' : 'SBE account' ?> — credentials, sessions, activity &amp; effective access.</div>
  </div>
  <a class="btn" href="users.php?tab=<?= $tab ?>">← Back to directory</a>
</div>

<?php if ($msg): ?><div class="alert ok"><?= sa_es($msg) ?></div><?php endif; ?>
<?php if ($err): ?><div class="alert err"><?= sa_es($err) ?></div><?php endif; ?>

<?php if ($u): ?>

<div class="grid g2">
  <div class="card">
    <h4>Account details</h4>
    <table>
      <tbody>
        <?php if ($tab === 'portal'): ?>
        <tr><td class="muted">User ID</td><td><b>#<?= (int)$u['user_id'] ?></b></td></tr>
        <tr><td class="muted">Full Name</td><td><b><?= sa_es($u['full_name']) ?></b></td></tr>
        <tr><td class="muted">Username</td><td><code><?= sa_es($u['username']) ?></code></td></tr>
        <tr><td class="muted">Login ID</td><td><?= $u['login_id'] ? '<code>' . sa_es($u['login_id']) . '</code>' : '—' ?></td></tr>
        <tr><td class="muted">Email</td><td><?= sa_es($u['email'] ?? '—') ?></td></tr>
        <tr><td class="muted">Phone</td><td><?= sa_es($u['phone'] ?? '—') ?></td></tr>
        <tr><td class="muted">Role</td><td><span class="pill b"><?= sa_es($u['role_name'] ?? '-') ?></span></td></tr>
        <tr><td class="muted">Department</td><td><?= sa_es($u['department_name'] ?? '—') ?></td></tr>
        <tr><td class="muted">Last Login</td><td><?= $u['last_login_at'] ? sa_es($u['last_login_at']) : '—' ?></td></tr>
        <tr><td class="muted">Created</td><td><?= sa_es($u['created_at']) ?></td></tr>
        <?php else: ?>
        <tr><td class="muted">Auth ID</td><td><b>#<?= (int)$u['auth_id'] ?></b></td></tr>
        <tr><td class="muted">Display Name</td><td><b><?= sa_es($u['display_name']) ?></b></td></tr>
        <tr><td class="muted">Login ID</td><td><code><?= sa_es($u['login_id']) ?></code></td></tr>
        <tr><td class="muted">Role</td><td><span class="pill i"><?= sa_es($u['role']) ?></span></td></tr>
        <tr><td class="muted">Teacher ID</td><td><?= $u['teacher_id'] ? '#' . (int)$u['teacher_id'] : '—' ?></td></tr>
        <tr><td class="muted">Student ID</td><td><?= $u['student_id'] ? '#' . (int)$u['student_id'] : '—' ?></td></tr>
        <tr><td class="muted">Department</td><td><?= sa_es($u['department_name'] ?? '—') ?></td></tr>
        <tr><td class="muted">Created</td><td><?= sa_es($u['created_at']) ?></td></tr>
        <?php endif; ?>
        <tr>
          <td class="muted">Account State</td>
          <td>
            <?php if ($ctl === 'Blocked'): ?><span class="pill r">Blocked</span>
            <?php elseif ($ctl === 'Frozen'): ?><span class="pill y">Frozen</span>
            <?php elseif (($u['status'] ?? 'Active') === 'Active'): ?><span class="pill g">Active</span>
            <?php else: ?><span class="pill n">Inactive</span><?php endif; ?>
            <?php if ($u['ctl_reason']): ?><div class="muted text-sm mt-2">Reason: <?= sa_es($u['ctl_reason']) ?></div><?php endif; ?>
          </td>
        </tr>
      </tbody>
    </table>
  </div>

  <div>
    <div class="card">
      <h4>Credentials — master control</h4>
      <p class="text-sm muted" style="margin-bottom:14px">Passwords are stored as hashes and can never be revealed in plain text. The Super Admin can override (reset) the password instantly. Any override is permanently audit-logged.</p>
      <form method="post">
        <input type="hidden" name="action" value="reset_password">
        <div class="field"><label>New password</label><input type="text" name="new_password" minlength="4" required placeholder="Enter a new password for this account"></div>
        <button class="btn btn-warn" type="submit">Reset password (override)</button>
      </form>
    </div>

    <div class="card">
      <h4>Account control</h4>
      <div class="flex" style="gap:8px;flex-wrap:wrap">
        <?php if ($ctl === 'Blocked'): ?>
          <form method="post"><input type="hidden" name="action" value="set_control"><input type="hidden" name="status" value="Active"><button class="btn btn-primary">Unblock</button></form>
        <?php else: ?>
          <form method="post"><input type="hidden" name="action" value="set_control"><input type="hidden" name="status" value="Blocked"><input type="hidden" name="reason" value=""><button class="btn btn-danger">Block account</button></form>
          <form method="post"><input type="hidden" name="action" value="set_control"><input type="hidden" name="status" value="Frozen"><input type="hidden" name="reason" value=""><button class="btn btn-warn">Freeze account</button></form>
        <?php endif; ?>
        <form method="post" onsubmit="return confirm('Terminate all live sessions for this account?')">
          <input type="hidden" name="action" value="terminate_all">
          <button class="btn">Terminate all sessions</button>
        </form>
      </div>
      <div class="muted text-sm mt-3">Blocking/freezing instantly kills all live sessions of this account.</div>
    </div>

    <div class="card">
      <h4>Live sessions (<?= count($sessions) ?>)</h4>
      <?php if (!$sessions): ?>
      <div class="empty" style="padding:16px">No live sessions right now.</div>
      <?php else: ?>
      <table>
        <thead><tr><th>Session</th><th>Type</th><th>Active</th><th></th></tr></thead>
        <tbody>
          <?php foreach ($sessions as $s): ?>
          <tr>
            <td class="mono"><?= sa_es(substr($s['sess_id'], 0, 18)) ?>…</td>
            <td class="text-sm"><?= $s['user_type'] === 'portal' ? 'SSO portal' : 'SBE portal' ?></td>
            <td class="muted text-sm"><?= sa_ago($s['mtime']) ?></td>
            <td><button class="btn btn-sm btn-danger" onclick="sa_kill('<?= sa_es($s['sess_id']) ?>', '<?= sa_es($u['full_name'] ?? $u['display_name'] ?? '') ?>')">Kill</button></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
      <?php endif; ?>
    </div>
  </div>
</div>

<?php if ($tab === 'portal'): ?>
<div class="card">
  <h4>Effective access summary (per module)</h4>
  <p class="text-sm muted" style="margin-bottom:14px">What this user can actually reach today, after resolving role, department and user-level rules. Super Admin-style unrestricted users are marked <span class="pill y">ROOT</span>.</p>
  <table>
    <thead><tr><th>Module</th><th>Submodule</th><th>Effective Access</th></tr></thead>
    <tbody>
      <?php
      $isRoot = strtolower($u['role_name'] ?? '') === 'super admin';
      $mods = mysqli_query($conn, "SELECT * FROM sa_modules WHERE status='Active' ORDER BY sort_order, module_id");
      while ($m = mysqli_fetch_assoc($mods)):
        $subs = mysqli_query($conn, "SELECT * FROM sa_submodules WHERE module_id={$m['module_id']} AND status='Active' ORDER BY sort_order, submodule_id");
        $rowspan = 1 + mysqli_num_rows($subs);
      ?>
      <tr>
        <td rowspan="<?= $rowspan ?>">
          <b><?= sa_es($m['module_name']) ?></b>
          <?php if ($isRoot): ?><span class="pill y">ROOT</span><?php endif; ?>
        </td>
        <?php if (mysqli_num_rows($subs) === 0): ?>
        <td class="muted">(module-level)</td>
        <td><span class="pill <?= $isRoot ? 'y' : (sa_has_access($m['module_id'], null, $u) ? 'g' : 'r') ?>"><?= $isRoot ? 'Unrestricted' : (sa_has_access($m['module_id'], null, $u) ? 'Allowed' : 'Denied') ?></span></td>
        </tr>
        <?php else: ?>
        <?php $first = true; while ($s = mysqli_fetch_assoc($subs)): ?>
        <?php if (!$first): ?><tr><?php endif; $first = false; ?>
          <td><?= sa_es($s['submodule_name']) ?></td>
          <td><span class="pill <?= $isRoot ? 'y' : (sa_has_access($m['module_id'], $s['submodule_id'], $u) ? 'g' : 'r') ?>"><?= $isRoot ? 'Unrestricted' : (sa_has_access($m['module_id'], $s['submodule_id'], $u) ? 'Allowed' : 'Denied') ?></span></td>
        </tr>
        <?php endwhile; ?>
        <?php endif; ?>
      <?php endwhile; ?>
    </tbody>
  </table>
</div>

<div class="card">
  <h4>Activity history (last 50)</h4>
  <?php if (!$activity || mysqli_num_rows($activity) === 0): ?>
  <div class="empty">No recorded activity for this user.</div>
  <?php else: ?>
  <table>
    <thead><tr><th>Time</th><th>Module</th><th>Action</th><th>Reference</th><th>Details</th></tr></thead>
    <tbody>
      <?php while ($r = mysqli_fetch_assoc($activity)): ?>
      <tr>
        <td class="muted text-sm"><?= sa_es($r['created_at']) ?></td>
        <td><b><?= sa_es($r['module']) ?></b></td>
        <td class="text-sm"><?= sa_es($r['action']) ?></td>
        <td class="muted text-sm"><?= sa_es($r['reference_table'] ?? '') ?><?= $r['reference_id'] ? '#' . (int)$r['reference_id'] : '' ?></td>
        <td class="muted text-sm"><?= sa_es(mb_substr((string)($r['details'] ?? ''), 0, 90)) ?></td>
      </tr>
      <?php endwhile; ?>
    </tbody>
  </table>
  <?php endif; ?>
</div>
<?php endif; ?>

<script>
function sa_kill(sessId, name){
  if (!confirm('Terminate this session (' + name + ')?')) return;
  fetch('api.php', {
    method: 'POST',
    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
    body: new URLSearchParams({action: 'terminate_session', session_id: sessId})
  }).then(function(r){ return r.json(); }).then(function(d){
    if (d.ok) location.reload(); else alert(d.error || 'Failed.');
  });
}
</script>

<?php endif; include __DIR__ . '/includes/footer.php'; ?>
