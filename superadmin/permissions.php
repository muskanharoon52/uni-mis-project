<?php
$sa_page_title = 'Permissions';
define('SA_INIT', true);
require_once __DIR__ . '/includes/auth.php';
sa_guard();
$conn = getConnection();

$scope = $_GET['scope'] ?? 'role';
$scope = in_array($scope, ['role', 'department', 'user'], true) ? $scope : 'role';
$granteeId = (int)($_GET['grantee'] ?? 0);

$moduleFilter = (int)($_GET['module_id'] ?? 0);

$roles = mysqli_query($conn, "SELECT * FROM roles ORDER BY role_id");
$depts = mysqli_query($conn, "SELECT * FROM departments ORDER BY department_name");
$users = mysqli_query($conn, "SELECT u.user_id, u.full_name, u.username, r.role_name FROM users u LEFT JOIN roles r ON u.role_id=r.role_id ORDER BY u.full_name");

$granteeLabel = '';
if ($scope === 'role' && $granteeId > 0) {
    $r = mysqli_fetch_assoc(mysqli_query($conn, "SELECT role_name FROM roles WHERE role_id=$granteeId"));
    $granteeLabel = $r['role_name'] ?? '';
} elseif ($scope === 'department' && $granteeId > 0) {
    $r = mysqli_fetch_assoc(mysqli_query($conn, "SELECT department_name FROM departments WHERE department_id=$granteeId"));
    $granteeLabel = $r['department_name'] ?? '';
} elseif ($scope === 'user' && $granteeId > 0) {
    $r = mysqli_fetch_assoc(mysqli_query($conn, "SELECT full_name, username FROM users WHERE user_id=$granteeId"));
    $granteeLabel = ($r['full_name'] ?? '') . ' (' . ($r['username'] ?? '') . ')';
}

$modules = mysqli_query($conn, "SELECT * FROM sa_modules ORDER BY sort_order, module_id");
$subs = mysqli_query($conn, "SELECT * FROM sa_submodules ORDER BY sort_order, submodule_id");

$sa_active = 'permissions';
include __DIR__ . '/includes/header.php';
?>

<div class="page-title">Granular Access &amp; Permission Control</div>
<div class="page-sub">Grant or revoke access to any module or submodule for a role, a department, or an individual user. Changes are saved instantly and synced live. The Super Admin role is never restricted.</div>

<div class="filters">
  <select id="scopeSel" onchange="location='permissions.php?scope='+this.value">
    <option value="role" <?= $scope === 'role' ? 'selected' : '' ?>>Scope: Role</option>
    <option value="department" <?= $scope === 'department' ? 'selected' : '' ?>>Scope: Department</option>
    <option value="user" <?= $scope === 'user' ? 'selected' : '' ?>>Scope: User</option>
  </select>

  <select id="granteeSel" onchange="location='permissions.php?scope=<?= $scope ?>&grantee='+this.value">
    <option value="0">— Select <?= ucfirst($scope) ?> —</option>
    <?php if ($scope === 'role'): ?>
      <?php mysqli_data_seek($roles, 0); while ($r = mysqli_fetch_assoc($roles)): ?>
      <option value="<?= (int)$r['role_id'] ?>" <?= $granteeId === (int)$r['role_id'] ? 'selected' : '' ?>><?= sa_es($r['role_name']) ?></option>
      <?php endwhile; ?>
    <?php elseif ($scope === 'department'): ?>
      <?php mysqli_data_seek($depts, 0); while ($r = mysqli_fetch_assoc($depts)): ?>
      <option value="<?= (int)$r['department_id'] ?>" <?= $granteeId === (int)$r['department_id'] ? 'selected' : '' ?>><?= sa_es($r['department_name']) ?></option>
      <?php endwhile; ?>
    <?php else: ?>
      <?php mysqli_data_seek($users, 0); while ($r = mysqli_fetch_assoc($users)): ?>
      <option value="<?= (int)$r['user_id'] ?>" <?= $granteeId === (int)$r['user_id'] ? 'selected' : '' ?>><?= sa_es($r['full_name']) ?> (@<?= sa_es($r['username']) ?>)</option>
      <?php endwhile; ?>
    <?php endif; ?>
  </select>
</div>

<?php if (!$granteeId): ?>
  <div class="card"><div class="empty">Select a role, department, or user above to manage its module &amp; submodule access matrix.</div></div>
<?php else: ?>

<div class="alert ok" id="saveFlash" style="display:none">Permission updated and saved instantly.</div>

<div class="flex jc-between" style="margin-bottom:12px">
  <div>
    <span class="pill b"><?= ucfirst($scope) ?>: <b><?= sa_es($granteeLabel) ?></b></span>
    <span class="pill n" style="margin-left:6px">Default state for everything: <b>Allowed</b></span>
  </div>
  <div class="muted text-sm">Toggle <b>Deny</b> to revoke, <b>Allow</b> to grant, <b>Reset</b> to clear the rule.</div>
</div>

<?php $moduleCount = 0; mysqli_data_seek($modules, 0); while ($m = mysqli_fetch_assoc($modules)):
    if ($moduleFilter > 0 && (int)$m['module_id'] !== $moduleFilter) continue;
    $moduleCount++;
    $modAccess = sa_permission_for($m['module_id'], null, $scope, $granteeId);
    $modSubs = mysqli_query($conn, "SELECT * FROM sa_submodules WHERE module_id={$m['module_id']} AND status='Active' ORDER BY sort_order, submodule_id");
?>
<div class="card" id="mod-<?= (int)$m['module_id'] ?>">
  <div class="flex jc-between" style="margin-bottom:12px">
    <div class="flex" style="gap:10px">
      <span style="width:34px;height:34px;border-radius:9px;background:<?= sa_es($m['module_color']) ?>22;color:<?= sa_es($m['module_color']) ?>;display:flex;align-items:center;justify-content:center;font-size:.95rem"><?= sa_es($m['module_icon'] ?: '◫') ?></span>
      <div>
        <div style="font-weight:700"><?= sa_es($m['module_name']) ?></div>
        <div class="muted text-sm">module-level access</div>
      </div>
    </div>
    <div class="flex" style="gap:6px" data-ctl="<?= (int)$m['module_id'] ?>" data-sub="0" data-scope="<?= $scope ?>" data-grantee="<?= $granteeId ?>">
      <button class="btn btn-sm <?= $modAccess === 'allow' ? 'btn-primary' : '' ?>" data-v="allow">Allow</button>
      <button class="btn btn-sm btn-danger <?= $modAccess === 'deny' ? '' : '' ?>" style="<?= $modAccess === 'deny' ? 'background:rgba(239,68,68,.4);border-color:rgba(239,68,68,.6);color:#fff' : '' ?>" data-v="deny">Deny</button>
      <button class="btn btn-sm btn-ghost <?= $modAccess === null ? 'active' : '' ?>" data-v="reset" style="<?= $modAccess === null ? 'border-color:var(--indigo);color:#a5b4fc' : '' ?>">Reset</button>
    </div>
  </div>
  <?php if (mysqli_num_rows($modSubs) > 0): ?>
  <table>
    <thead><tr><th>Submodule</th><th style="width:300px">Access control</th></tr></thead>
    <tbody>
      <?php while ($s = mysqli_fetch_assoc($modSubs)):
        $subAccess = sa_permission_for($m['module_id'], $s['submodule_id'], $scope, $granteeId);
      ?>
      <tr id="sub-<?= (int)$s['submodule_id'] ?>">
        <td>
          <b><?= sa_es($s['submodule_name']) ?></b>
          <div class="muted text-sm"><code><?= sa_es($s['submodule_key']) ?></code> <?= $s['route'] ? '<span class="mono">→ ' . sa_es($s['route']) . '</span>' : '' ?></div>
        </td>
        <td>
          <div class="flex" style="gap:6px" data-ctl="<?= (int)$m['module_id'] ?>" data-sub="<?= (int)$s['submodule_id'] ?>" data-scope="<?= $scope ?>" data-grantee="<?= $granteeId ?>">
            <button class="btn btn-sm <?= $subAccess === 'allow' ? 'btn-primary' : '' ?>" data-v="allow">Allow</button>
            <button class="btn btn-sm btn-danger" style="<?= $subAccess === 'deny' ? 'background:rgba(239,68,68,.4);border-color:rgba(239,68,68,.6);color:#fff' : '' ?>" data-v="deny">Deny</button>
            <button class="btn btn-sm btn-ghost" style="<?= $subAccess === null ? 'border-color:var(--indigo);color:#a5b4fc' : '' ?>" data-v="reset">Reset</button>
          </div>
        </td>
      </tr>
      <?php endwhile; ?>
    </tbody>
  </table>
  <?php else: ?>
    <div class="empty" style="padding:14px">No active submodules under this module.</div>
  <?php endif; ?>
</div>
<?php endwhile; if ($moduleCount === 0): ?>
  <div class="card"><div class="empty">No modules registered. <a class="link" href="modules.php">Create modules</a> first.</div></div>
<?php endif; endif; ?>

<script>
document.querySelectorAll('[data-ctl]').forEach(function(ctl){
  ctl.querySelectorAll('button').forEach(function(btn){
    btn.addEventListener('click', function(){
      var v = btn.dataset.v;
      var mod = ctl.dataset.ctl, sub = ctl.dataset.sub, scope = ctl.dataset.scope, grantee = ctl.dataset.grantee;
      fetch('api.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: new URLSearchParams({action: v === 'reset' ? 'clear_permission' : 'set_permission', module_id: mod, submodule_id: sub, scope: scope, grantee: grantee, access: v})
      }).then(function(r){ return r.json(); }).then(function(d){
        if (d.ok) {
          var flash = document.getElementById('saveFlash');
          flash.style.display = 'block';
          setTimeout(function(){ flash.style.display = 'none'; }, 2000);
          // update button states
          ctl.querySelectorAll('button').forEach(function(b){
            b.classList.remove('btn-primary');
            b.style.background = ''; b.style.borderColor = ''; b.style.color = '';
            if (b.dataset.v === v) {
              if (v === 'allow') b.classList.add('btn-primary');
              if (v === 'deny') { b.style.background = 'rgba(239,68,68,.4)'; b.style.borderColor = 'rgba(239,68,68,.6)'; b.style.color = '#fff'; }
              if (v === 'reset') { b.style.borderColor = 'var(--indigo)'; b.style.color = '#a5b4fc'; }
            }
          });
        } else {
          alert(d.error || 'Failed to update permission.');
        }
      });
    });
  });
});
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
