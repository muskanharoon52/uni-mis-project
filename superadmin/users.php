<?php
$sa_page_title = 'Users';
define('SA_INIT', true);
require_once __DIR__ . '/includes/auth.php';
sa_guard();
$conn = getConnection();

$tab = $_GET['tab'] ?? 'portal';
$tab = in_array($tab, ['portal', 'sbe'], true) ? $tab : 'portal';

$deptFilter = (int)($_GET['dept'] ?? 0);
$roleFilter = (int)($_GET['role'] ?? 0);
$statusFilter = $_GET['status'] ?? '';
$q = trim($_GET['q'] ?? '');

$onlineMap = [];
foreach (sa_scan_sessions() as $s) {
    if ($s['user_type'] === 'portal') $onlineMap[(int)$s['user_id']] = $s;
}

$roles = mysqli_query($conn, "SELECT * FROM roles ORDER BY role_id");
$depts = mysqli_query($conn, "SELECT * FROM departments ORDER BY department_name");

/* ---------------- portal users ---------------- */
$sql = "SELECT u.*, r.role_name, c.status ctl_status, c.reason ctl_reason,
        COALESCE(u.department_id, t.department_id, p.department_id) dept_id,
        d.department_name
        FROM users u
        LEFT JOIN roles r ON r.role_id = u.role_id
        LEFT JOIN teachers t ON t.user_id = u.user_id
        LEFT JOIN students st ON st.user_id = u.user_id
        LEFT JOIN programs p ON p.program_id = st.program_id
        LEFT JOIN departments d ON d.department_id = COALESCE(u.department_id, t.department_id, p.department_id)
        LEFT JOIN sa_user_controls c ON c.user_type = 'portal' AND c.user_id = u.user_id
        WHERE 1=1";
if ($deptFilter > 0) $sql .= " AND COALESCE(u.department_id, t.department_id, p.department_id) = $deptFilter";
if ($roleFilter > 0) $sql .= " AND u.role_id = $roleFilter";
if ($statusFilter === 'Active') $sql .= " AND COALESCE(c.status,'Active') = 'Active' AND u.status = 'Active'";
if ($statusFilter === 'Inactive') $sql .= " AND u.status = 'Inactive' AND COALESCE(c.status,'Active') NOT IN ('Blocked','Frozen')";
if ($statusFilter === 'Blocked') $sql .= " AND COALESCE(c.status,'') = 'Blocked'";
if ($statusFilter === 'Frozen') $sql .= " AND COALESCE(c.status,'') = 'Frozen'";
if ($q !== '') {
    $like = mysqli_real_escape_string($conn, $q);
    $sql .= " AND (u.full_name LIKE '%$like%' OR u.username LIKE '%$like%' OR u.login_id LIKE '%$like%' OR u.email LIKE '%$like%')";
}
$sql .= " ORDER BY u.user_id";
$portalUsers = mysqli_query($conn, $sql);

/* ---------------- sbe users ---------------- */
$sbeSql = "SELECT a.*, t.department_id t_dept, p.department_id s_dept, c.status ctl_status, c.reason ctl_reason,
           d.department_name
           FROM sbe_auth_users a
           LEFT JOIN teachers t ON t.teacher_id = a.teacher_id
           LEFT JOIN students st ON st.student_id = a.student_id
           LEFT JOIN programs p ON p.program_id = st.program_id
           LEFT JOIN departments d ON d.department_id = COALESCE(t.department_id, p.department_id)
           LEFT JOIN sa_user_controls c ON c.user_type = 'sbe' AND c.user_id = a.auth_id
           WHERE 1=1";
if ($deptFilter > 0) $sbeSql .= " AND COALESCE(t.department_id, p.department_id) = $deptFilter";
if ($statusFilter === 'Blocked') $sbeSql .= " AND COALESCE(c.status,'') = 'Blocked'";
if ($statusFilter === 'Frozen') $sbeSql .= " AND COALESCE(c.status,'') = 'Frozen'";
if ($q !== '') {
    $like = mysqli_real_escape_string($conn, $q);
    $sbeSql .= " AND (a.login_id LIKE '%$like%' OR a.display_name LIKE '%$like%')";
}
$sbeSql .= " ORDER BY a.auth_id";
$sbeUsers = mysqli_query($conn, $sbeSql);

$sbeOnlineMap = [];
foreach (sa_scan_sessions() as $s) {
    if ($s['user_type'] === 'sbe') $sbeOnlineMap[(int)$s['sbe_auth_id']] = $s;
}

$sa_active = 'users';
include __DIR__ . '/includes/header.php';
?>

<div class="page-title">User Governance &amp; Directory</div>
<div class="page-sub">Search, filter and monitor every account. View profiles, credentials, live sessions, activity history, and take instant account-control actions.</div>

<div class="tabs">
  <a class="tab <?= $tab === 'portal' ? 'active' : '' ?>" href="users.php?tab=portal">Portal Users</a>
  <a class="tab <?= $tab === 'sbe' ? 'active' : '' ?>" href="users.php?tab=sbe">SBE Accounts</a>
</div>

<form class="filters" method="get">
  <input type="hidden" name="tab" value="<?= $tab ?>">
  <select name="dept">
    <option value="0">All Departments</option>
    <?php mysqli_data_seek($depts, 0); while ($d = mysqli_fetch_assoc($depts)): ?>
    <option value="<?= (int)$d['department_id'] ?>" <?= $deptFilter === (int)$d['department_id'] ? 'selected' : '' ?>><?= sa_es($d['department_name']) ?></option>
    <?php endwhile; ?>
  </select>
  <?php if ($tab === 'portal'): ?>
  <select name="role">
    <option value="0">All Roles</option>
    <?php mysqli_data_seek($roles, 0); while ($r = mysqli_fetch_assoc($roles)): ?>
    <option value="<?= (int)$r['role_id'] ?>" <?= $roleFilter === (int)$r['role_id'] ? 'selected' : '' ?>><?= sa_es($r['role_name']) ?></option>
    <?php endwhile; ?>
  </select>
  <?php endif; ?>
  <select name="status">
    <option value="">All Statuses</option>
    <option value="Active" <?= $statusFilter === 'Active' ? 'selected' : '' ?>>Active</option>
    <option value="Inactive" <?= $statusFilter === 'Inactive' ? 'selected' : '' ?>>Inactive</option>
    <option value="Blocked" <?= $statusFilter === 'Blocked' ? 'selected' : '' ?>>Blocked</option>
    <option value="Frozen" <?= $statusFilter === 'Frozen' ? 'selected' : '' ?>>Frozen</option>
  </select>
  <input type="text" name="q" value="<?= sa_es($q) ?>" placeholder="Search name, username, login id, email...">
  <button class="btn btn-primary" type="submit">Filter</button>
  <a class="btn" href="users.php?tab=<?= $tab ?>">Clear</a>
</form>

<?php if ($tab === 'portal'): ?>
<div class="card">
  <?php if (mysqli_num_rows($portalUsers) === 0): ?>
  <div class="empty">No users match the current filters.</div>
  <?php else: ?>
  <table>
    <thead><tr><th>User</th><th>Role</th><th>Department</th><th>Status</th><th>Session</th><th>Last Login</th><th style="width:290px">Actions</th></tr></thead>
    <tbody>
      <?php while ($u = mysqli_fetch_assoc($portalUsers)):
        $ctl = $u['ctl_status'] ?? 'Active';
        $eff = $ctl !== 'Active' ? $ctl : $u['status'];
        $isOnline = isset($onlineMap[(int)$u['user_id']]);
      ?>
      <tr>
        <td>
          <div style="display:flex;align-items:center;gap:9px">
            <div class="avatar" style="width:30px;height:30px;font-size:.72rem"><?= sa_es(mb_substr($u['full_name'], 0, 1)) ?></div>
            <div>
              <b><?= sa_es($u['full_name']) ?></b>
              <div class="muted text-sm">@<?= sa_es($u['username']) ?> <?= $u['login_id'] ? '· <code>' . sa_es($u['login_id']) . '</code>' : '' ?></div>
            </div>
          </div>
        </td>
        <td><span class="pill <?= strtolower((string)$u['role_name']) === 'super admin' ? 'y' : 'b' ?>"><?= sa_es($u['role_name'] ?? '-') ?></span></td>
        <td class="text-sm"><?= sa_es($u['department_name'] ?? '—') ?></td>
        <td>
          <?php if ($ctl === 'Blocked'): ?><span class="pill r">Blocked</span>
          <?php elseif ($ctl === 'Frozen'): ?><span class="pill y">Frozen</span>
          <?php elseif ($u['status'] === 'Active'): ?><span class="pill g">Active</span>
          <?php else: ?><span class="pill n">Inactive</span><?php endif; ?>
          <?php if ($u['ctl_reason']): ?><div class="muted text-sm mt-2"><?= sa_es($u['ctl_reason']) ?></div><?php endif; ?>
        </td>
        <td><?= $isOnline ? '<span class="pill g">● Online</span>' : '<span class="pill n">Offline</span>' ?></td>
        <td class="muted text-sm"><?= $u['last_login_at'] ? sa_es($u['last_login_at']) : '—' ?></td>
        <td>
          <div class="flex" style="gap:6px;flex-wrap:wrap">
            <a class="btn btn-sm" href="user_profile.php?id=<?= (int)$u['user_id'] ?>">Profile</a>
            <?php if ($ctl === 'Blocked'): ?>
              <button class="btn btn-sm btn-primary" onclick="sa_control('portal', <?= (int)$u['user_id'] ?>, 'Active', '<?= sa_es($u['full_name']) ?>')">Unblock</button>
            <?php else: ?>
              <button class="btn btn-sm btn-danger" onclick="sa_control('portal', <?= (int)$u['user_id'] ?>, 'Blocked', '<?= sa_es($u['full_name']) ?>')">Block</button>
              <button class="btn btn-sm btn-warn" onclick="sa_control('portal', <?= (int)$u['user_id'] ?>, 'Frozen', '<?= sa_es($u['full_name']) ?>')">Freeze</button>
            <?php endif; ?>
            <?php if ($isOnline): ?>
              <button class="btn btn-sm" onclick="sa_terminate_all('portal', <?= (int)$u['user_id'] ?>, '<?= sa_es($u['full_name']) ?>')">Kill sessions</button>
            <?php endif; ?>
          </div>
        </td>
      </tr>
      <?php endwhile; ?>
    </tbody>
  </table>
  <?php endif; ?>
</div>

<?php else: /* SBE tab */ ?>
<div class="card">
  <p class="text-sm muted" style="margin-bottom:12px">SBE (System Based Examination) accounts live in the separate <code>sbe_auth_users</code> table with their own login. Governance actions below work instantly: block/freeze/unblock and terminate live SBE sessions.</p>
  <?php if (mysqli_num_rows($sbeUsers) === 0): ?>
  <div class="empty">No SBE accounts match the current filters.</div>
  <?php else: ?>
  <table>
    <thead><tr><th>Account</th><th>Role</th><th>Department</th><th>Status</th><th>Session</th><th style="width:290px">Actions</th></tr></thead>
    <tbody>
      <?php while ($a = mysqli_fetch_assoc($sbeUsers)):
        $ctl = $a['ctl_status'] ?? 'Active';
        $isOnline = isset($sbeOnlineMap[(int)$a['auth_id']]);
        $baseStatus = $ctl !== 'Active' ? $ctl : $a['status'];
      ?>
      <tr>
        <td>
          <div style="display:flex;align-items:center;gap:9px">
            <div class="avatar" style="width:30px;height:30px;font-size:.72rem;background:linear-gradient(135deg,#0ea5e9,#0369a1)"><?= sa_es(mb_substr($a['display_name'], 0, 1)) ?></div>
            <div>
              <b><?= sa_es($a['display_name']) ?></b>
              <div class="muted text-sm">Login ID <code><?= sa_es($a['login_id']) ?></code></div>
            </div>
          </div>
        </td>
        <td><span class="pill i"><?= sa_es($a['role']) ?></span></td>
        <td class="text-sm"><?= sa_es($a['department_name'] ?? '—') ?></td>
        <td>
          <?php if ($ctl === 'Blocked'): ?><span class="pill r">Blocked</span>
          <?php elseif ($ctl === 'Frozen'): ?><span class="pill y">Frozen</span>
          <?php elseif ($a['status'] === 'Active'): ?><span class="pill g">Active</span>
          <?php else: ?><span class="pill n">Inactive</span><?php endif; ?>
        </td>
        <td><?= $isOnline ? '<span class="pill g">● Online</span>' : '<span class="pill n">Offline</span>' ?></td>
        <td>
          <div class="flex" style="gap:6px;flex-wrap:wrap">
            <a class="btn btn-sm" href="user_profile.php?tab=sbe&id=<?= (int)$a['auth_id'] ?>">Profile</a>
            <?php if ($ctl === 'Blocked'): ?>
              <button class="btn btn-sm btn-primary" onclick="sa_control('sbe', <?= (int)$a['auth_id'] ?>, 'Active', '<?= sa_es($a['display_name']) ?>')">Unblock</button>
            <?php else: ?>
              <button class="btn btn-sm btn-danger" onclick="sa_control('sbe', <?= (int)$a['auth_id'] ?>, 'Blocked', '<?= sa_es($a['display_name']) ?>')">Block</button>
              <button class="btn btn-sm btn-warn" onclick="sa_control('sbe', <?= (int)$a['auth_id'] ?>, 'Frozen', '<?= sa_es($a['display_name']) ?>')">Freeze</button>
            <?php endif; ?>
            <?php if ($isOnline): ?>
              <button class="btn btn-sm" onclick="sa_terminate_all('sbe', <?= (int)$a['auth_id'] ?>, '<?= sa_es($a['display_name']) ?>')">Kill sessions</button>
            <?php endif; ?>
          </div>
        </td>
      </tr>
      <?php endwhile; ?>
    </tbody>
  </table>
  <?php endif; ?>
</div>
<?php endif; ?>

<script>
function sa_control(userType, userId, status, name){
  var reason = prompt((status === 'Blocked' ? 'Block' : status === 'Frozen' ? 'Freeze' : 'Unblock') + ' account "' + name + '"?\nEnter a reason (optional):', '');
  if (reason === null) return;
  fetch('api.php', {
    method: 'POST',
    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
    body: new URLSearchParams({action: 'set_control', user_type: userType, user_id: userId, status: status, reason: reason})
  }).then(function(r){ return r.json(); }).then(function(d){
    if (d.ok) { location.reload(); }
    else alert(d.error || 'Failed.');
  });
}
function sa_terminate_all(userType, userId, name){
  if (!confirm('Terminate ALL live sessions for "' + name + '"?')) return;
  fetch('api.php', {
    method: 'POST',
    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
    body: new URLSearchParams({action: 'terminate_all', user_type: userType, user_id: userId})
  }).then(function(r){ return r.json(); }).then(function(d){
    if (d.ok) { location.reload(); }
    else alert(d.error || 'Failed.');
  });
}
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
