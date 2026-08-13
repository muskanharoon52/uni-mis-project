<?php
$sa_page_title = 'Module Access — Submodules';
define('SA_INIT', true);
require_once __DIR__ . '/includes/auth.php';
sa_guard();
$conn = getConnection();
$me = (int)($_SESSION['user_id'] ?? 0);

$view = $_GET['view'] ?? 'all';
if (!in_array($view, ['all', 'grant', 'remove'], true)) {
    $view = 'all';
}

$msg = ''; $err = '';

/* ------------------------------ POST actions ------------------------------ */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    /* GRANT: attach a submodule to a main module (Active) */
    if ($action === 'grant_access') {
        $submoduleId = (int)($_POST['submodule_id'] ?? 0);
        $targetModule = (int)($_POST['target_module_id'] ?? 0);

        if ($submoduleId <= 0 || $targetModule <= 0) {
            $err = 'Select a submodule and a main module to grant access.';
        } else {
            $check = mysqli_query($conn, "SELECT module_id FROM sa_submodules WHERE submodule_id=$submoduleId");
            $oldModule = ($row = mysqli_fetch_assoc($check)) ? (int)$row['module_id'] : 0;

            $stmt = $conn->prepare("UPDATE sa_submodules SET module_id = ?, status = 'Active' WHERE submodule_id = ?");
            $stmt->bind_param('ii', $targetModule, $submoduleId);
            if ($stmt->execute()) {
                $name = mysqli_fetch_assoc(mysqli_query($conn, "SELECT submodule_name FROM sa_submodules WHERE submodule_id=$submoduleId"))['submodule_name'] ?? "submodule #$submoduleId";
                $msg = "Access granted — '$name' is now active in the target module's sidebar.";
                sa_log('GRANT_SUBMODULE', 'submodule', $submoduleId, "Granted submodule #$submoduleId to module #$targetModule" . ($oldModule && $oldModule !== $targetModule ? " (moved from module #$oldModule)" : ''));
            } else {
                $err = $conn->error;
            }
            $stmt->close();
        }
    }

    /* REMOVE: unchecking a submodule removes it from the main module */
    if ($action === 'save_access') {
        $filterModule = (int)($_POST['module_id'] ?? 0);
        $checked = array_map('intval', (array) ($_POST['submodule_ids'] ?? []));

        if ($filterModule <= 0) {
            $err = 'Select a main module before saving.';
        } else {
            $all = [];
            $allResult = mysqli_query($conn, "SELECT submodule_id, status FROM sa_submodules WHERE module_id=$filterModule");
            if ($allResult) {
                while ($row = mysqli_fetch_assoc($allResult)) {
                    $all[(int)$row['submodule_id']] = $row['status'];
                }
            }

            $toActivate = [];
            $toDeactivate = [];
            foreach ($all as $sid => $status) {
                $isChecked = in_array($sid, $checked, true);
                if ($isChecked && $status !== 'Active') {
                    $toActivate[] = $sid;
                } elseif (!$isChecked && $status === 'Active') {
                    $toDeactivate[] = $sid;
                }
            }

            if ($toDeactivate) {
                $ids = implode(',', array_map('intval', $toDeactivate));
                mysqli_query($conn, "UPDATE sa_submodules SET status='Inactive' WHERE submodule_id IN ($ids) AND module_id=$filterModule");
                sa_log('REMOVE_SUBMODULE', 'module', $filterModule, "Removed access for submodule(s): " . implode(',', $toDeactivate));
            }
            if ($toActivate) {
                $ids = implode(',', array_map('intval', $toActivate));
                mysqli_query($conn, "UPDATE sa_submodules SET status='Active' WHERE submodule_id IN ($ids) AND module_id=$filterModule");
                sa_log('GRANT_SUBMODULE', 'module', $filterModule, "Restored submodule(s): " . implode(',', $toActivate));
            }

            if ($toDeactivate || $toActivate) {
                $msg = 'Access updated — ' . count($toDeactivate) . ' submodule(s) removed, ' . count($toActivate) . ' submodule(s) granted.';
            } else {
                $msg = 'No changes were made.';
            }
        }
    }
}

/* ------------------------------ data queries ------------------------------ */
$modules = mysqli_query($conn, "SELECT module_id, module_name FROM sa_modules WHERE status='Active' ORDER BY sort_order, module_id");

$filterModule = $view === 'remove' ? (int)($_GET['module_id'] ?? 0) : 0;

/* ALL: every submodule of every main module except dashboards */
$allRows = [];
if ($view === 'all') {
    $res = mysqli_query($conn, "SELECT s.submodule_id, s.module_id, s.submodule_key, s.submodule_name, s.route, s.status,
            m.module_name, m.module_key
        FROM sa_submodules s
        JOIN sa_modules m ON m.module_id = s.module_id
        WHERE s.submodule_key <> 'dashboard'
        ORDER BY m.sort_order, m.module_id, s.sort_order, s.submodule_id");
    if ($res) {
        while ($row = mysqli_fetch_assoc($res)) {
            $allRows[] = $row;
        }
    }
}

/* GRANT: all grantable submodules (non-dashboard) grouped by their current module */
$grantRows = [];
if ($view === 'grant') {
    $res = mysqli_query($conn, "SELECT s.submodule_id, s.module_id, s.submodule_key, s.submodule_name, s.route, s.status,
            m.module_name, m.module_key
        FROM sa_submodules s
        JOIN sa_modules m ON m.module_id = s.module_id
        WHERE s.submodule_key <> 'dashboard'
        ORDER BY m.sort_order, m.module_id, s.sort_order, s.submodule_id");
    if ($res) {
        while ($row = mysqli_fetch_assoc($res)) {
            $grantRows[] = $row;
        }
    }
}

/* REMOVE: submodules of the selected main module */
$removeRows = [];
$title = null;
if ($view === 'remove' && $filterModule > 0) {
    $res = mysqli_query($conn, "SELECT s.* FROM sa_submodules s WHERE s.module_id=$filterModule ORDER BY s.sort_order, s.submodule_id");
    if ($res) {
        while ($row = mysqli_fetch_assoc($res)) {
            $removeRows[] = $row;
        }
    }
    $titleResult = mysqli_query($conn, "SELECT module_name FROM sa_modules WHERE module_id=$filterModule");
    if ($titleResult) {
        $title = mysqli_fetch_assoc($titleResult);
    }
}

$activeCount = 0;
foreach ($removeRows as $s) {
    if ($s['status'] === 'Active') {
        $activeCount++;
    }
}

$sa_active = $view === 'grant' ? 'grant' : ($view === 'remove' ? 'remove' : 'submodules');
include __DIR__ . '/includes/header.php';
?>

<div class="tabs" style="margin-bottom:20px">
  <a class="tab<?= $view === 'all' ? ' active' : '' ?>" href="submodules.php">All Submodules</a>
  <a class="tab<?= $view === 'grant' ? ' active' : '' ?>" href="submodules.php?view=grant">Grant Access</a>
  <a class="tab<?= $view === 'remove' ? ' active' : '' ?>" href="submodules.php?view=remove">Remove Access</a>
</div>

<?php if ($msg): ?><div class="alert ok"><?= sa_es($msg) ?></div><?php endif; ?>
<?php if ($err): ?><div class="alert err"><?= sa_es($err) ?></div><?php endif; ?>

<?php if ($view === 'all'): ?>
<!-- ==================== ALL SUBMODULES ==================== -->
<div class="flex jc-between" style="margin-bottom:4px">
  <div>
    <div class="page-title">All Submodules</div>
    <div class="page-sub">Every submodule of every main module (dashboards excluded). This is the registry Super Admin manages.</div>
  </div>
  <span class="badge admin"><?= count($allRows) ?> submodules</span>
</div>

<div class="card">
  <?php if (empty($allRows)): ?>
    <div class="empty">No submodules registered yet.</div>
  <?php else: ?>
  <table>
    <thead>
      <tr>
        <th style="width:60px">#</th>
        <th>Main Module</th>
        <th>Submodule</th>
        <th>Route</th>
        <th style="width:110px">Status</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($allRows as $i => $s): ?>
      <tr>
        <td class="muted"><?= (int)$s['submodule_id'] ?></td>
        <td><b><?= sa_es($s['module_name']) ?></b><br><span class="muted text-sm"><?= sa_es($s['module_key']) ?></span></td>
        <td><?= sa_es($s['submodule_name']) ?><br><span class="muted text-sm"><?= sa_es($s['submodule_key']) ?></span></td>
        <td><code><?= sa_es($s['route'] ?? '') ?></code></td>
        <td><span class="pill <?= $s['status'] === 'Active' ? 'g' : 'n' ?>"><?= $s['status'] === 'Active' ? 'Active' : 'Removed' ?></span></td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
  <?php endif; ?>
</div>
<?php endif; ?>

<?php if ($view === 'grant'): ?>
<!-- ==================== GRANT ACCESS ==================== -->
<div class="flex jc-between" style="margin-bottom:4px">
  <div>
    <div class="page-title">Grant Access</div>
    <div class="page-sub">Pick a submodule from the list, then choose the main module it should appear in, and click Give Access.</div>
  </div>
</div>

<div class="card">
  <?php if (empty($grantRows)): ?>
    <div class="empty">No submodules registered yet.</div>
  <?php else: ?>
  <form method="post" id="grantForm">
    <input type="hidden" name="action" value="grant_access">

    <div class="field" style="display:flex;align-items:center;gap:12px;flex-wrap:wrap;margin-bottom:16px;">
      <label style="margin:0;min-width:110px;display:flex;align-items:center;gap:8px;">
        <input type="radio" name="target_radio" checked> Radio select
      </label>
    </div>

    <table>
      <thead>
        <tr>
          <th style="width:44px"></th>
          <th>Submodule</th>
          <th>Currently in Module</th>
          <th>Status</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($grantRows as $s): ?>
        <tr>
          <td><input type="radio" name="submodule_id" value="<?= (int)$s['submodule_id'] ?>"></td>
          <td><b><?= sa_es($s['submodule_name']) ?></b><br><span class="muted text-sm"><?= sa_es($s['submodule_key']) ?></span></td>
          <td><?= sa_es($s['module_name']) ?></td>
          <td><span class="pill <?= $s['status'] === 'Active' ? 'g' : 'n' ?>"><?= $s['status'] === 'Active' ? 'Active' : 'Removed' ?></span></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>

    <div class="field" style="margin-top:18px;max-width:420px;">
      <label>Give access to this main module</label>
      <select name="target_module_id" required>
        <option value="0">— Select a main module —</option>
        <?php
        if ($modules) {
            mysqli_data_seek($modules, 0);
            while ($m = mysqli_fetch_assoc($modules)) {
                echo '<option value="' . (int)$m['module_id'] . '">' . sa_es($m['module_name']) . '</option>';
            }
        }
        ?>
      </select>
    </div>

    <div class="field" style="margin-top:8px;">
      <button type="submit" class="btn btn-primary">Give Access</button>
      <span class="muted text-sm" style="margin-left:10px">The submodule will show in that main module's sidebar.</span>
    </div>
  </form>
  <?php endif; ?>
</div>

<script>
document.addEventListener('change', function (e) {
  if (e.target && e.target.name === 'submodule_id') {
    document.getElementById('grantForm').querySelector('input[name="target_radio"]').checked = true;
  }
});
</script>
<?php endif; ?>

<?php if ($view === 'remove'): ?>
<!-- ==================== REMOVE ACCESS ==================== -->
<div class="flex jc-between" style="margin-bottom:4px">
  <div>
    <div class="page-title"><?= $filterModule > 0 && $title ? 'Remove Access — ' . sa_es($title['module_name']) : 'Remove Access' ?></div>
    <div class="page-sub">Select a main module first, then uncheck any submodule to remove it from that module's sidebar.</div>
  </div>
</div>

<div class="filters" style="margin-bottom:16px">
  <select id="moduleSelect" onchange="if(this.value){window.location='submodules.php?view=remove&module_id='+this.value;}" style="min-width:280px">
    <option value="0">— Select a main module —</option>
    <?php
    if ($modules) {
        mysqli_data_seek($modules, 0);
        while ($m = mysqli_fetch_assoc($modules)) {
            $sel = $filterModule === (int)$m['module_id'] ? ' selected' : '';
            echo '<option value="' . (int)$m['module_id'] . '"' . $sel . '>' . sa_es($m['module_name']) . '</option>';
        }
    }
    ?>
  </select>
  <?php if ($filterModule > 0 && $title): ?>
    <span class="badge admin"><?= (int)$activeCount ?> of <?= count($removeRows) ?> submodules active</span>
  <?php endif; ?>
</div>

<div class="module-grid" style="margin-bottom:18px">
  <?php
  if ($modules) {
      mysqli_data_seek($modules, 0);
      while ($m = mysqli_fetch_assoc($modules)):
  ?>
  <a class="mod-card<?= $filterModule === (int)$m['module_id'] ? ' active' : '' ?>" href="submodules.php?view=remove&module_id=<?= (int)$m['module_id'] ?>" style="display:block;text-decoration:none;color:inherit;">
    <div class="m-name"><?= sa_es($m['module_name']) ?></div>
    <div class="m-key">Module ID <?= (int)$m['module_id'] ?></div>
  </a>
  <?php
      endwhile;
  }
  ?>
</div>

<div class="card">
  <?php if (empty($removeRows)): ?>
    <div class="empty"><?= $filterModule > 0 ? 'No submodules registered for this module yet.' : 'Select a main module above (dropdown or cards) to view its submodules.' ?></div>
  <?php else: ?>
  <form method="post" id="accessForm">
    <input type="hidden" name="action" value="save_access">
    <input type="hidden" name="module_id" value="<?= $filterModule ?>">
    <div class="field" style="margin-bottom:14px;display:flex;align-items:center;gap:12px;flex-wrap:wrap;">
      <button type="submit" class="btn btn-primary">Save Changes</button>
      <span class="muted text-sm">Uncheck a submodule to remove access from this module.</span>
    </div>

  <table>
    <thead>
      <tr>
        <th style="width:44px"><input type="checkbox" id="checkAll" onchange="toggleAll(this)" title="Select / deselect all"></th>
        <th>Submodule</th>
        <th>Route</th>
        <th style="width:110px">Status</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($removeRows as $s): ?>
      <?php $isActive = $s['status'] === 'Active'; ?>
      <tr>
        <td><input type="checkbox" name="submodule_ids[]" value="<?= (int)$s['submodule_id'] ?>" <?= $isActive ? 'checked' : '' ?>></td>
        <td><b><?= sa_es($s['submodule_name']) ?></b><br><span class="muted text-sm"><?= sa_es($s['description'] ?? '') ?></span></td>
        <td><code><?= sa_es($s['route'] ?? '') ?></code></td>
        <td><span class="pill <?= $isActive ? 'g' : 'n' ?>"><?= $isActive ? 'Active' : 'Removed' ?></span></td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
  </form>
  <?php endif; ?>
</div>

<script>
function toggleAll(source) {
  document.querySelectorAll('#accessForm input[name="submodule_ids[]"]').forEach(function (cb) {
    cb.checked = source.checked;
  });
}
</script>
<?php endif; ?>

<?php include __DIR__ . '/includes/footer.php'; ?>
