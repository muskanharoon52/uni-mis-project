<?php
$sa_page_title = 'Live Sessions';
define('SA_INIT', true);
require_once __DIR__ . '/includes/auth.php';
sa_guard();
$conn = getConnection();

$sessions = sa_scan_sessions();

$portalCount = count(array_filter($sessions, fn($s) => $s['user_type'] === 'portal'));
$sbeCount    = count(array_filter($sessions, fn($s) => $s['user_type'] === 'sbe'));

// enrich with department + control status
foreach ($sessions as $i => $s) {
    $dept = '';
    $ctl = 'Active';
    if ($s['user_type'] === 'portal') {
        $row = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COALESCE(u.department_id, t.department_id, p.department_id) dept_id, d.department_name, c.status ctl
            FROM users u
            LEFT JOIN teachers t ON t.user_id=u.user_id
            LEFT JOIN students st ON st.user_id=u.user_id
            LEFT JOIN programs p ON p.program_id=st.program_id
            LEFT JOIN departments d ON d.department_id=COALESCE(u.department_id, t.department_id, p.department_id)
            LEFT JOIN sa_user_controls c ON c.user_type='portal' AND c.user_id=u.user_id
            WHERE u.user_id={$s['user_id']} LIMIT 1"));
        if ($row) { $dept = $row['department_name'] ?? ''; $ctl = $row['ctl'] ?? 'Active'; }
    } else {
        $row = mysqli_fetch_assoc(mysqli_query($conn, "SELECT d.department_name, c.status ctl
            FROM sbe_auth_users a
            LEFT JOIN teachers t ON t.teacher_id=a.teacher_id
            LEFT JOIN students st ON st.student_id=a.student_id
            LEFT JOIN programs p ON p.program_id=st.program_id
            LEFT JOIN departments d ON d.department_id=COALESCE(t.department_id, p.department_id)
            LEFT JOIN sa_user_controls c ON c.user_type='sbe' AND c.user_id=a.auth_id
            WHERE a.auth_id={$s['sbe_auth_id']} LIMIT 1"));
        if ($row) { $dept = $row['department_name'] ?? ''; $ctl = $row['ctl'] ?? 'Active'; }
    }
    $sessions[$i]['department'] = $dept;
    $sessions[$i]['ctl'] = $ctl;
}

$sa_active = 'sessions';
include __DIR__ . '/includes/header.php';
?>

<div class="flex jc-between" style="margin-bottom:4px">
  <div>
    <div class="page-title">Live Session Monitor</div>
    <div class="page-sub">All currently logged-in users across the SSO portal and the SBE portal, read in real time from active PHP sessions.</div>
  </div>
  <button class="btn" onclick="location.reload()">⟳ Refresh</button>
</div>

<div class="grid g3 mt-3">
  <div class="kpi"><div class="k-lbl">Total live sessions</div><div class="k-val"><?= count($sessions) ?></div></div>
  <div class="kpi"><div class="k-lbl">SSO portal sessions</div><div class="k-val"><?= $portalCount ?></div><div class="k-lbl"><a class="link" href="users.php?tab=portal">open directory →</a></div></div>
  <div class="kpi"><div class="k-lbl">SBE portal sessions</div><div class="k-val"><?= $sbeCount ?></div><div class="k-lbl"><a class="link" href="users.php?tab=sbe">open directory →</a></div></div>
</div>

<div class="card mt-3">
  <?php if (!$sessions): ?>
  <div class="empty">No live sessions. Log into the SSO or SBE portal from another browser to see sessions appear here.</div>
  <?php else: ?>
  <table>
    <thead><tr><th>Identity</th><th>Portal</th><th>Role</th><th>Department</th><th>State</th><th>Last activity</th><th style="width:130px">Action</th></tr></thead>
    <tbody>
      <?php foreach ($sessions as $s): ?>
      <tr>
        <td>
          <?php if ($s['user_type'] === 'portal'): ?>
            <b><?= sa_es($s['full_name'] ?: ('User #' . $s['user_id'])) ?></b>
            <div class="muted text-sm">@<?= sa_es($s['username']) ?></div>
          <?php else: ?>
            <b><?= sa_es($s['full_name'] ?: ('SBE #' . $s['sbe_auth_id'])) ?></b>
            <div class="muted text-sm">Login <code><?= sa_es($s['sbe_login_id']) ?></code></div>
          <?php endif; ?>
          <div class="mono text-sm" style="margin-top:2px"><?= sa_es(substr($s['sess_id'], 0, 20)) ?>…</div>
        </td>
        <td><span class="pill <?= $s['user_type'] === 'portal' ? 'b' : 'i' ?>"><?= $s['user_type'] === 'portal' ? 'SSO' : 'SBE' ?></span></td>
        <td class="text-sm"><?= $s['user_type'] === 'portal' ? sa_es($s['role_name']) : sa_es($s['sbe_role']) ?></td>
        <td class="text-sm"><?= sa_es($s['department'] ?: '—') ?></td>
        <td>
          <?php if ($s['ctl'] === 'Blocked'): ?><span class="pill r">Blocked</span>
          <?php elseif ($s['ctl'] === 'Frozen'): ?><span class="pill y">Frozen</span>
          <?php else: ?><span class="pill g">Active</span><?php endif; ?>
        </td>
        <td class="muted text-sm"><?= sa_ago($s['mtime']) ?></td>
        <td>
          <button class="btn btn-sm btn-danger" onclick="sa_kill('<?= sa_es($s['sess_id']) ?>')">Terminate</button>
        </td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
  <?php endif; ?>
</div>

<script>
function sa_kill(sessId){
  if (!confirm('Terminate this live session?')) return;
  fetch('api.php', {
    method: 'POST',
    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
    body: new URLSearchParams({action: 'terminate_session', session_id: sessId})
  }).then(function(r){ return r.json(); }).then(function(d){
    if (d.ok) location.reload(); else alert(d.error || 'Failed.');
  });
}
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
