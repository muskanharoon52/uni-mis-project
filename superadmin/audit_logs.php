<?php
$sa_page_title = 'Audit Logs';
define('SA_INIT', true);
require_once __DIR__ . '/includes/auth.php';
sa_guard();
$conn = getConnection();

$tab = $_GET['tab'] ?? 'system';
$tab = $tab === 'admin' ? 'admin' : 'system';

$from = $_GET['from'] ?? '';
$to = $_GET['to'] ?? '';
$userId = (int)($_GET['user_id'] ?? 0);
$module = trim($_GET['module'] ?? '');
$actionType = trim($_GET['action_type'] ?? '');
$q = trim($_GET['q'] ?? '');

$actionTypes = ['', 'Create', 'Read', 'Update', 'Delete', 'Login', 'Logout'];

function sa_where($from, $to, $userId, $module, $actionType, $q, $conn) {
    $w = "1=1";
    if ($from !== '') $w .= " AND created_at >= '$from 00:00:00'";
    if ($to !== '') $w .= " AND created_at <= '$to 23:59:59'";
    if ($userId > 0) $w .= " AND performed_by = $userId";
    if ($module !== '') $w .= " AND module = '" . mysqli_real_escape_string($conn, $module) . "'";
    if ($actionType !== '') $w .= " AND action LIKE '%" . mysqli_real_escape_string($conn, $actionType) . "%'";
    if ($q !== '') $w .= " AND (details LIKE '%" . mysqli_real_escape_string($conn, $q) . "%' OR action LIKE '%" . mysqli_real_escape_string($conn, $q) . "%')";
    return $w;
}

if ($tab === 'system') {
    $where = sa_where($from, $to, $userId, $module, $actionType, $q, $conn);
    $total = (int)(mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) c FROM activity_logs WHERE $where"))['c'] ?? 0);
    $rows = mysqli_query($conn, "SELECT l.*, u.full_name user_name FROM activity_logs l LEFT JOIN users u ON u.user_id=l.performed_by WHERE $where ORDER BY l.log_id DESC LIMIT 500");
    $modules = mysqli_query($conn, "SELECT DISTINCT module FROM activity_logs ORDER BY module");
    $users = mysqli_query($conn, "SELECT user_id, full_name, username FROM users ORDER BY full_name");
} else {
    $w = "1=1";
    if ($from !== '') $w .= " AND created_at >= '$from 00:00:00'";
    if ($to !== '') $w .= " AND created_at <= '$to 23:59:59'";
    if ($userId > 0) $w .= " AND admin_user_id = $userId";
    if ($actionType !== '') $w .= " AND action LIKE '%" . mysqli_real_escape_string($conn, $actionType) . "%'";
    if ($q !== '') $w .= " AND (details LIKE '%" . mysqli_real_escape_string($conn, $q) . "%' OR action LIKE '%" . mysqli_real_escape_string($conn, $q) . "%')";
    $total = (int)(mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) c FROM sa_audit_logs WHERE $w"))['c'] ?? 0);
    $rows = mysqli_query($conn, "SELECT a.*, u.full_name admin_name FROM sa_audit_logs a LEFT JOIN users u ON u.user_id=a.admin_user_id WHERE $w ORDER BY a.audit_id DESC LIMIT 500");
    $users = mysqli_query($conn, "SELECT user_id, full_name, username FROM users ORDER BY full_name");
}

$sa_active = 'audit';
include __DIR__ . '/includes/header.php';
?>

<div class="page-title">System-Wide Audit &amp; Activity Logs</div>
<div class="page-sub">Trace every action across SSO, Finance, Examination, LMS and SBE — plus the permanent record of every sensitive Super Admin action.</div>

<div class="tabs">
  <a class="tab <?= $tab === 'system' ? 'active' : '' ?>" href="audit_logs.php?tab=system">Module Activity (all modules)</a>
  <a class="tab <?= $tab === 'admin' ? 'active' : '' ?>" href="audit_logs.php?tab=admin">Super Admin Audit</a>
</div>

<form class="filters" method="get">
  <input type="hidden" name="tab" value="<?= $tab ?>">
  <input type="date" name="from" value="<?= sa_es($from) ?>">
  <input type="date" name="to" value="<?= sa_es($to) ?>">
  <select name="user_id">
    <option value="0">All users</option>
    <?php mysqli_data_seek($users, 0); while ($u = mysqli_fetch_assoc($users)): ?>
    <option value="<?= (int)$u['user_id'] ?>" <?= $userId === (int)$u['user_id'] ? 'selected' : '' ?>><?= sa_es($u['full_name']) ?> (@<?= sa_es($u['username']) ?>)</option>
    <?php endwhile; ?>
  </select>
  <?php if ($tab === 'system'): ?>
  <select name="module">
    <option value="">All modules</option>
    <?php mysqli_data_seek($modules, 0); while ($m = mysqli_fetch_assoc($modules)): ?>
    <option value="<?= sa_es($m['module']) ?>" <?= $module === $m['module'] ? 'selected' : '' ?>><?= sa_es($m['module']) ?></option>
    <?php endwhile; ?>
  </select>
  <?php endif; ?>
  <select name="action_type">
    <option value="">All actions</option>
    <?php foreach ($actionTypes as $at): if ($at === '') continue; ?>
    <option value="<?= $at ?>" <?= $actionType === $at ? 'selected' : '' ?>><?= $at ?></option>
    <?php endforeach; ?>
  </select>
  <input type="text" name="q" value="<?= sa_es($q) ?>" placeholder="Search action / details...">
  <button class="btn btn-primary" type="submit">Filter</button>
  <a class="btn" href="audit_logs.php?tab=<?= $tab ?>">Clear</a>
</form>

<div class="muted text-sm" style="margin-bottom:14px"><b><?= $total ?></b> log entr<?= $total === 1 ? 'y' : 'ies' ?> match<?= $total === 1 ? 'es' : '' ?> (showing up to 500).</div>

<div class="card">
  <?php if ($total === 0): ?>
  <div class="empty">No log entries match the current filters.</div>
  <?php else: ?>
  <table>
    <thead>
      <tr>
        <th>Time</th>
        <?php if ($tab === 'system'): ?><th>Module</th><?php endif; ?>
        <th>Action</th>
        <th>User</th>
        <th>Reference</th>
        <th>Details</th>
      </tr>
    </thead>
    <tbody>
      <?php while ($r = mysqli_fetch_assoc($rows)): ?>
      <tr>
        <td class="muted text-sm"><?= sa_es($r['created_at']) ?></td>
        <?php if ($tab === 'system'): ?><td><span class="pill b"><?= sa_es($r['module']) ?></span></td><?php endif; ?>
        <td><span class="pill <?= strpos(strtolower($r['action']), 'delete') !== false || strpos(strtolower($r['action']), 'block') !== false ? 'r' : 'g' ?>"><?= sa_es($r['action']) ?></span></td>
        <td class="text-sm"><?= sa_es($r['user_name'] ?? $r['admin_name'] ?? ('#' . ($r['performed_by'] ?? $r['admin_user_id'] ?? ''))) ?></td>
        <td class="muted text-sm"><?= sa_es($r['reference_table'] ?? $r['target_type'] ?? '') ?><?= ($r['reference_id'] ?? $r['target_id'] ?? 0) ? '#' . (int)($r['reference_id'] ?? $r['target_id']) : '' ?></td>
        <td class="muted text-sm" style="max-width:380px"><?= sa_es(mb_substr((string)($r['details'] ?? ''), 0, 140)) ?></td>
      </tr>
      <?php endwhile; ?>
    </tbody>
  </table>
  <?php endif; ?>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
