<?php
$sa_page_title = 'Department Access';
define('SA_INIT', true);
require_once __DIR__ . '/includes/auth.php';
sa_guard();
$conn = getConnection();
$me = (int) ($_SESSION['user_id'] ?? 0);

$msg = '';
$err = '';
$deptId = (int) ($_GET['dept'] ?? 0);
$moduleId = (int) ($_GET['module'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'save_access') {
        $deptId = (int) ($_POST['department_id'] ?? 0);
        $moduleId = (int) ($_POST['module_id'] ?? 0);
        $grantModule = !empty($_POST['grant_module']);
        $checked = array_map('intval', (array) ($_POST['submodule_ids'] ?? []));

        if ($deptId <= 0 || $moduleId <= 0) {
            $err = 'Select a department and a main module first.';
        } else {
            $deptName = mysqli_fetch_assoc(mysqli_query($conn, "SELECT department_name FROM departments WHERE department_id=$deptId"))['department_name'] ?? "#$deptId";
            $modName = mysqli_fetch_assoc(mysqli_query($conn, "SELECT module_name FROM sa_modules WHERE module_id=$moduleId"))['module_name'] ?? "#$moduleId";

            // Replace all department rules for this module with the new selection.
            mysqli_query($conn, "DELETE FROM sa_permissions WHERE grantee_type='department' AND grantee_id=$deptId AND module_id=$moduleId");

            $added = 0;
            if ($grantModule) {
                $stmt = $conn->prepare("INSERT INTO sa_permissions (module_id, submodule_id, grantee_type, grantee_id, access, created_by) VALUES (?, NULL, 'department', ?, 'allow', ?)");
                $stmt->bind_param('iii', $moduleId, $deptId, $me);
                if ($stmt->execute()) {
                    $added++;
                }
                $stmt->close();
            }
            foreach ($checked as $sid) {
                $sid = max(1, $sid);
                $stmt = $conn->prepare("INSERT INTO sa_permissions (module_id, submodule_id, grantee_type, grantee_id, access, created_by) VALUES (?, ?, 'department', ?, 'allow', ?)");
                $stmt->bind_param('iiii', $moduleId, $sid, $deptId, $me);
                if ($stmt->execute()) {
                    $added++;
                }
                $stmt->close();
            }

            $msg = "Access updated for $deptName — $added grant(s) saved for $modName.";
            sa_log('DEPT_ACCESS', 'department', $deptId, "Set module #$moduleId ($modName) access for department #$deptId ($deptName): $added grant(s)");
        }
    }
}

$departments = mysqli_query($conn, "SELECT department_id, department_name FROM departments ORDER BY department_name");
$modules = mysqli_query($conn, "SELECT module_id, module_name, module_key, module_icon, module_color FROM sa_modules WHERE status='Active' ORDER BY sort_order, module_id");

// Existing department rules for the selected module (module-level + submodule-level).
$moduleGranted = false;
$grantedSubs = [];
if ($deptId > 0 && $moduleId > 0) {
    $res = mysqli_query($conn, "SELECT submodule_id, access FROM sa_permissions WHERE grantee_type='department' AND grantee_id=$deptId AND module_id=$moduleId");
    while ($r = mysqli_fetch_assoc($res)) {
        if ($r['submodule_id'] === null) {
            $moduleGranted = $r['access'] === 'allow';
        } else {
            $grantedSubs[(int) $r['submodule_id']] = $r['access'] === 'allow';
        }
    }
}

$sa_active = 'department_access';
include __DIR__ . '/includes/header.php';
?>

<div class="flex jc-between" style="margin-bottom:4px">
  <div>
    <div class="page-title">Department Access</div>
    <div class="page-sub">Grant submodules to a department for any main module. The selected submodules are shown in that main module's side panel for the department's users.</div>
  </div>
</div>

<?php if ($msg): ?><div class="alert ok"><?= sa_es($msg) ?></div><?php endif; ?>
<?php if ($err): ?><div class="alert err"><?= sa_es($err) ?></div><?php endif; ?>

<form method="get" class="filters">
  <select name="module" onchange="this.form.submit()">
    <option value="0">— Select Main Module —</option>
    <?php mysqli_data_seek($modules, 0); while ($m = mysqli_fetch_assoc($modules)): ?>
    <option value="<?= (int) $m['module_id'] ?>" <?= $moduleId === (int) $m['module_id'] ? 'selected' : '' ?>><?= sa_es($m['module_name']) ?></option>
    <?php endwhile; ?>
  </select>
  <?php if ($deptId > 0 || $moduleId > 0): ?>
    <a class="btn btn-sm" href="department_access.php">Clear</a>
  <?php endif; ?>
</form>

<?php if ($deptId <= 0 || $moduleId <= 0): ?>
  <div class="card"><div class="empty">Select a department and a main module above to manage its submodule grants. Dashboard entries are always available and are not listed.</div></div>
<?php else:
    $subs = mysqli_query($conn, "SELECT * FROM sa_submodules WHERE module_id=$moduleId AND status='Active' AND submodule_key <> 'dashboard' ORDER BY sort_order, submodule_id");
    $deptName = mysqli_fetch_assoc(mysqli_query($conn, "SELECT department_name FROM departments WHERE department_id=$deptId"))['department_name'] ?? "#$deptId";
    $modName = mysqli_fetch_assoc(mysqli_query($conn, "SELECT module_name, module_color FROM sa_modules WHERE module_id=$moduleId")) ?? null;
?>
<div class="card" style="margin-top:14px">
  <div class="flex jc-between" style="margin-bottom:14px">
    <div class="flex" style="gap:10px">
      <span style="width:34px;height:34px;border-radius:9px;background:<?= sa_es($modName['module_color'] ?? '#6366f1') ?>22;color:<?= sa_es($modName['module_color'] ?? '#6366f1') ?>;display:flex;align-items:center;justify-content:center;font-size:.95rem"><?= sa_es($modName['module_icon'] ?? '◫') ?></span>
      <div>
        <div style="font-weight:700"><?= sa_es($modName['module_name'] ?? $moduleId) ?> — <?= sa_es($deptName) ?></div>
        <div class="muted text-sm">Check the submodules this department may access. Unchecked submodules are hidden from the side panel.</div>
      </div>
    </div>
  </div>

  <form method="post">
    <input type="hidden" name="action" value="save_access">
    <input type="hidden" name="department_id" value="<?= $deptId ?>">
    <input type="hidden" name="module_id" value="<?= $moduleId ?>">

    <label class="flex" style="gap:10px;margin-bottom:16px;cursor:pointer">
      <input type="checkbox" name="grant_module" value="1" <?= $moduleGranted ? 'checked' : '' ?>>
      <span><b>Grant the whole module</b> <span class="muted text-sm">(module-level access — every submodule is accessible)</span></span>
    </label>

    <?php if (mysqli_num_rows($subs) === 0): ?>
      <div class="empty" style="padding:16px">No active submodules (besides the dashboard) registered for this module yet. Create them on the <a class="link" href="submodules.php?module_id=<?= $moduleId ?>">Submodules</a> page.</div>
    <?php else: ?>
      <div class="module-grid" style="grid-template-columns:repeat(auto-fill,minmax(260px,1fr))">
        <?php mysqli_data_seek($subs, 0); while ($s = mysqli_fetch_assoc($subs)): ?>
        <label class="mod-card" style="cursor:pointer;display:flex;align-items:flex-start;gap:12px">
          <input type="checkbox" name="submodule_ids[]" value="<?= (int) $s['submodule_id'] ?>" <?= !empty($grantedSubs[(int) $s['submodule_id']]) ? 'checked' : '' ?> style="margin-top:4px">
          <div>
            <div class="m-name"><?= sa_es($s['submodule_name']) ?></div>
            <div class="m-key"><code><?= sa_es($s['submodule_key']) ?></code></div>
            <div class="muted text-sm" style="word-break:break-all"><?= sa_es($s['route'] ?? '') ?></div>
          </div>
        </label>
        <?php endwhile; ?>
      </div>

      <div class="flex jc-between mt-3">
        <div class="muted text-sm">Hint: the department's users will see only the submodules you check here in the side panel of this module.</div>
        <button type="submit" class="btn btn-primary">Save Grants</button>
      </div>
    <?php endif; ?>
  </form>
</div>
<?php endif; ?>

<?php include __DIR__ . '/includes/footer.php'; ?>