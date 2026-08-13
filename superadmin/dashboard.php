<?php
$sa_page_title = 'Dashboard';
define('SA_INIT', true);
require_once __DIR__ . '/includes/auth.php';
sa_guard();
$conn = getConnection();

$totalUsers   = (int)(mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) c FROM users"))['c'] ?? 0);
$activeUsers  = (int)(mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) c FROM users WHERE status='Active'"))['c'] ?? 0);
$totalRoles   = (int)(mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) c FROM roles"))['c'] ?? 0);
$totalModules = (int)(mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) c FROM sa_modules WHERE status='Active'"))['c'] ?? 0);
$totalSubs    = (int)(mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) c FROM sa_submodules WHERE status='Active'"))['c'] ?? 0);
$totalPerms   = (int)(mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) c FROM sa_permissions"))['c'] ?? 0);
$blocked      = (int)(mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) c FROM sa_user_controls WHERE status IN ('Blocked','Frozen')"))['c'] ?? 0);

$online = sa_scan_sessions();
$portalOnline = count(array_filter($online, fn($s) => $s['user_type'] === 'portal'));
$sbeOnline    = count(array_filter($online, fn($s) => $s['user_type'] === 'sbe'));

$modules = mysqli_query($conn, "SELECT m.*, (SELECT COUNT(*) FROM sa_submodules s WHERE s.module_id = m.module_id) sub_count FROM sa_modules m ORDER BY m.sort_order, m.module_id");

$recentAudit = mysqli_query($conn, "SELECT a.*, u.full_name admin_name FROM sa_audit_logs a LEFT JOIN users u ON u.user_id = a.admin_user_id ORDER BY a.audit_id DESC LIMIT 8");

$recentActivity = mysqli_query($conn, "SELECT l.*, u.full_name user_name FROM activity_logs l LEFT JOIN users u ON u.user_id = l.performed_by ORDER BY l.log_id DESC LIMIT 8");

$sa_active = 'dashboard';
include __DIR__ . '/includes/header.php';
?>

<div class="page-title">Super Admin Dashboard</div>
<div class="page-sub">Full unrestricted control &amp; governance across every module of the platform.</div>

<?php if (mysqli_num_rows($modules) === 0): ?>
<div class="alert err">No modules registered yet. <a href="modules.php" class="link">Create the module registry</a> first.</div>
<?php endif; ?>

<div class="grid g4">
  <div class="kpi"><div class="k-top"><div class="k-lbl">Registered Users</div><div class="k-ic" style="background:rgba(99,102,241,.15);color:#818cf8">👤</div></div><div class="k-val"><?= $totalUsers ?></div><div class="k-lbl"><?= $activeUsers ?> active</div></div>
  <div class="kpi"><div class="k-top"><div class="k-lbl">System Roles</div><div class="k-ic" style="background:rgba(34,197,94,.15);color:#4ade80">🏷</div></div><div class="k-val"><?= $totalRoles ?></div><div class="k-lbl">RBAC matrix ready</div></div>
  <div class="kpi"><div class="k-top"><div class="k-lbl">Live Sessions</div><div class="k-ic" style="background:rgba(56,189,248,.15);color:#38bdf8">◎</div></div><div class="k-val"><?= $portalOnline + $sbeOnline ?></div><div class="k-lbl"><?= $portalOnline ?> portal · <?= $sbeOnline ?> SBE</div></div>
  <div class="kpi"><div class="k-top"><div class="k-lbl">Access Rules</div><div class="k-ic" style="background:rgba(245,158,11,.15);color:#fbbf24">⚙</div></div><div class="k-val"><?= $totalPerms ?></div><div class="k-lbl"><?= $blocked ?> account(s) blocked/frozen</div></div>
</div>

<div class="grid g4 mt-3">
  <div class="kpi"><div class="k-top"><div class="k-lbl">Modules</div><div class="k-ic" style="background:rgba(139,92,246,.15);color:#a78bfa">◫</div></div><div class="k-val"><?= $totalModules ?></div><div class="k-lbl"><a class="link" href="modules.php">manage registry →</a></div></div>
  <div class="kpi"><div class="k-top"><div class="k-lbl">Submodules</div><div class="k-ic" style="background:rgba(236,72,153,.15);color:#f472b6">▤</div></div><div class="k-val"><?= $totalSubs ?></div><div class="k-lbl"><a class="link" href="submodules.php">move / rewire →</a></div></div>
  <div class="kpi"><div class="k-top"><div class="k-lbl">Sensitive Admin Actions</div><div class="k-ic" style="background:rgba(239,68,68,.15);color:#f87171">📜</div></div><div class="k-val"><?= (int)(mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) c FROM sa_audit_logs"))['c'] ?? 0) ?></div><div class="k-lbl"><a class="link" href="audit_logs.php">audit trail →</a></div></div>
  <div class="kpi"><div class="k-top"><div class="k-lbl">Departments</div><div class="k-ic" style="background:rgba(14,165,233,.15);color:#38bdf8">🏛</div></div><div class="k-val"><?= (int)(mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) c FROM departments WHERE status='Active'"))['c'] ?? 0) ?></div><div class="k-lbl"><a class="link" href="users.php">browse users →</a></div></div>
</div>

<div class="card mt-3">
  <h4>Modules under governance</h4>
  <div class="module-grid">
    <?php while ($m = mysqli_fetch_assoc($modules)): ?>
    <div class="mod-card" style="--mc:<?= sa_es($m['module_color'] ?? '#6366f1') ?>">
      <div>
        <div class="m-name"><?= sa_es($m['module_name']) ?></div>
        <div class="m-key"><?= sa_es($m['module_key']) ?></div>
      </div>
      <div class="m-stats">
        <span><b><?= (int)$m['sub_count'] ?></b> submodules</span>
        <span><b><?= (int)(mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) c FROM sa_permissions WHERE module_id = {$m['module_id']}"))['c'] ?? 0) ?></b> rules</span>
        <span class="pill <?= $m['status'] === 'Active' ? 'g' : 'n' ?>"><?= sa_es($m['status']) ?></span>
      </div>
      <div class="m-actions">
        <a class="btn btn-sm" href="submodules.php?module_id=<?= (int)$m['module_id'] ?>">Submodules</a>
        <a class="btn btn-sm btn-ghost" href="modules.php?edit=<?= (int)$m['module_id'] ?>">Edit</a>
      </div>
    </div>
    <?php endwhile; ?>
  </div>
</div>

<div class="grid g2 mt-3">
  <div class="card">
    <h4>Latest super admin actions</h4>
    <?php if (mysqli_num_rows($recentAudit) > 0): ?>
    <table>
      <thead><tr><th>Action</th><th>Target</th><th>By</th><th>When</th></tr></thead>
      <tbody>
        <?php while ($r = mysqli_fetch_assoc($recentAudit)): ?>
        <tr>
          <td><span class="pill <?= in_array($r['action'], ['TERMINATE_SESSION','BLOCK_USER','FREEZE_USER']) ? 'r' : 'b' ?>"><?= sa_es($r['action']) ?></span></td>
          <td class="muted text-sm"><?= sa_es($r['target_type']) ?><?= $r['target_id'] ? '#' . (int)$r['target_id'] : '' ?></td>
          <td class="text-sm"><?= sa_es($r['admin_name'] ?? ('#' . $r['admin_user_id'])) ?></td>
          <td class="muted text-sm"><?= sa_es($r['created_at']) ?></td>
        </tr>
        <?php endwhile; ?>
      </tbody>
    </table>
    <?php else: ?>
    <div class="empty">No super admin actions logged yet.</div>
    <?php endif; ?>
  </div>

  <div class="card">
    <h4>Latest system activity</h4>
    <?php if (mysqli_num_rows($recentActivity) > 0): ?>
    <table>
      <thead><tr><th>Module</th><th>Action</th><th>User</th><th>When</th></tr></thead>
      <tbody>
        <?php while ($r = mysqli_fetch_assoc($recentActivity)): ?>
        <tr>
          <td class="text-sm"><b><?= sa_es($r['module']) ?></b></td>
          <td class="muted text-sm"><?= sa_es($r['action']) ?></td>
          <td class="text-sm"><?= sa_es($r['user_name'] ?? ('#' . $r['performed_by'])) ?></td>
          <td class="muted text-sm"><?= sa_es($r['created_at']) ?></td>
        </tr>
        <?php endwhile; ?>
      </tbody>
    </table>
    <?php else: ?>
    <div class="empty">No activity recorded yet.</div>
    <?php endif; ?>
  </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>