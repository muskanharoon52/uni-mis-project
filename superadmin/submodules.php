<?php
$sa_page_title = 'Submodules';
define('SA_INIT', true);
require_once __DIR__ . '/includes/auth.php';
sa_guard();
$conn = getConnection();
$me = (int)($_SESSION['user_id'] ?? 0);

$msg = ''; $err = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add_sub') {
        $moduleId = (int)($_POST['module_id'] ?? 0);
        $key = strtolower(trim(preg_replace('/[^a-zA-Z0-9_-]/', '', $_POST['submodule_key'] ?? '')));
        $name = trim($_POST['submodule_name'] ?? '');
        $route = trim($_POST['route'] ?? '');
        $desc = trim($_POST['description'] ?? '');
        $sort = (int)($_POST['sort_order'] ?? 0);
        if ($moduleId <= 0 || $key === '' || $name === '') {
            $err = 'Module, submodule key and name are required.';
        } else {
            $stmt = $conn->prepare("INSERT INTO sa_submodules (module_id, submodule_key, submodule_name, route, description, sort_order) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param('issssi', $moduleId, $key, $name, $route, $desc, $sort);
            if ($stmt->execute()) {
                $sid = $stmt->insert_id;
                $mod = mysqli_fetch_assoc(mysqli_query($conn, "SELECT module_name FROM sa_modules WHERE module_id=$moduleId"));
                $msg = "Submodule '$name' created.";
                sa_log('CREATE_SUBMODULE', 'submodule', $sid, "Created submodule '$name' under module '" . ($mod['module_name'] ?? $moduleId) . "'");
            } else $err = $conn->error;
        }
    }

    if ($action === 'update_sub') {
        $id = (int)($_POST['submodule_id'] ?? 0);
        $moduleId = (int)($_POST['module_id'] ?? 0);
        $key = strtolower(trim(preg_replace('/[^a-zA-Z0-9_-]/', '', $_POST['submodule_key'] ?? '')));
        $name = trim($_POST['submodule_name'] ?? '');
        $route = trim($_POST['route'] ?? '');
        $desc = trim($_POST['description'] ?? '');
        $sort = (int)($_POST['sort_order'] ?? 0);
        $status = $_POST['status'] === 'Active' ? 'Active' : 'Inactive';
        $old = $id > 0 ? mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM sa_submodules WHERE submodule_id=$id")) : null;
        if ($old && $moduleId > 0 && $key !== '' && $name !== '') {
            $oldMod = mysqli_fetch_assoc(mysqli_query($conn, "SELECT module_name FROM sa_modules WHERE module_id={$old['module_id']}"));
            $newMod = mysqli_fetch_assoc(mysqli_query($conn, "SELECT module_name FROM sa_modules WHERE module_id=$moduleId"));
            $moved = (int)$old['module_id'] !== $moduleId;
            $stmt = $conn->prepare("UPDATE sa_submodules SET module_id=?, submodule_key=?, submodule_name=?, route=?, description=?, sort_order=?, status=? WHERE submodule_id=?");
            $stmt->bind_param('issssisi', $moduleId, $key, $name, $route, $desc, $sort, $status, $id);
            if ($stmt->execute()) {
                $msg = "Submodule #$id updated.";
                $detail = "Updated submodule '$name'";
                if ($moved) $detail .= " | MOVED from '" . ($oldMod['module_name'] ?? $old['module_id']) . "' to '" . ($newMod['module_name'] ?? $moduleId) . "'";
                sa_log($moved ? 'MOVE_SUBMODULE' : 'UPDATE_SUBMODULE', 'submodule', $id, $detail . " (status: $status)");
            } else $err = $conn->error;
        } else $err = 'Invalid submodule.';
    }

    if ($action === 'delete_sub') {
        $id = (int)($_POST['submodule_id'] ?? 0);
        $rules = (int)(mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) c FROM sa_permissions WHERE submodule_id=$id"))['c'] ?? 0);
        if ($id > 0 && $rules === 0) {
            $row = mysqli_fetch_assoc(mysqli_query($conn, "SELECT submodule_name FROM sa_submodules WHERE submodule_id=$id"));
            mysqli_query($conn, "DELETE FROM sa_submodules WHERE submodule_id=$id");
            $msg = "Submodule deleted.";
            sa_log('DELETE_SUBMODULE', 'submodule', $id, "Deleted submodule '" . ($row['submodule_name'] ?? '') . "'");
        } else {
            $err = "Cannot delete: $rules permission rule(s) reference this submodule. Clear them first.";
        }
    }
}

$filterModule = (int)($_GET['module_id'] ?? 0);

$modules = mysqli_query($conn, "SELECT module_id, module_name, module_color, module_icon FROM sa_modules ORDER BY sort_order, module_id");

if ($filterModule > 0) {
    $rows = mysqli_query($conn, "SELECT s.*, m.module_name parent_name FROM sa_submodules s JOIN sa_modules m ON m.module_id=s.module_id WHERE s.module_id=$filterModule ORDER BY s.sort_order, s.submodule_id");
    $title = mysqli_fetch_assoc(mysqli_query($conn, "SELECT module_name FROM sa_modules WHERE module_id=$filterModule"));
} else {
    $rows = mysqli_query($conn, "SELECT s.*, m.module_name parent_name FROM sa_submodules s JOIN sa_modules m ON m.module_id=s.module_id ORDER BY m.sort_order, s.module_id, s.sort_order");
}

$editId = (int)($_GET['edit'] ?? 0);
$editing = $editId > 0 ? mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM sa_submodules WHERE submodule_id=$editId")) : null;

$sa_active = 'submodules';
include __DIR__ . '/includes/header.php';
?>

<div class="flex jc-between" style="margin-bottom:4px">
  <div>
    <div class="page-title">Submodule Registry</div>
    <div class="page-sub">Every feature/tab across the platform. Move a submodule to another module, rename it, or disable it — the whole registry is customizable.</div>
  </div>
  <button class="btn btn-primary" onclick="openModal('addModal')">+ New Submodule</button>
</div>

<div class="filters" style="margin-top:14px">
  <select onchange="location='submodules.php?module_id='+this.value">
    <option value="0">All modules</option>
    <?php mysqli_data_seek($modules, 0); while ($m = mysqli_fetch_assoc($modules)): ?>
    <option value="<?= (int)$m['module_id'] ?>" <?= $filterModule === (int)$m['module_id'] ? 'selected' : '' ?>><?= sa_es($m['module_name']) ?></option>
    <?php endwhile; ?>
  </select>
  <?php if ($filterModule > 0 && $title): ?>
    <span class="pill b">Filtered: <?= sa_es($title['module_name']) ?></span>
    <a class="btn btn-sm" href="submodules.php">Clear filter</a>
  <?php endif; ?>
</div>

<?php if ($msg): ?><div class="alert ok"><?= sa_es($msg) ?></div><?php endif; ?>
<?php if ($err): ?><div class="alert err"><?= sa_es($err) ?></div><?php endif; ?>

<div class="card">
  <?php if (mysqli_num_rows($rows) === 0): ?>
  <div class="empty">No submodules registered yet.</div>
  <?php else: ?>
  <table>
    <thead><tr><th>ID</th><th>Submodule</th><th>Parent Module</th><th>Route</th><th>Status</th><th style="width:210px">Actions</th></tr></thead>
    <tbody>
      <?php while ($s = mysqli_fetch_assoc($rows)): ?>
      <tr>
        <td class="muted">#<?= (int)$s['submodule_id'] ?></td>
        <td><b><?= sa_es($s['submodule_name']) ?></b><div class="muted text-sm"><code><?= sa_es($s['submodule_key']) ?></code><?= $s['description'] ? ' — ' . sa_es($s['description']) : '' ?></div></td>
        <td>
          <span class="pill <?= $s['parent_name'] ? 'i' : 'n' ?>"><?= sa_es($s['parent_name'] ?? '-') ?></span>
        </td>
        <td class="mono"><?= sa_es($s['route'] ?? '-') ?></td>
        <td><span class="pill <?= $s['status'] === 'Active' ? 'g' : 'n' ?>"><?= sa_es($s['status']) ?></span></td>
        <td>
          <div class="flex" style="gap:6px">
            <a class="btn btn-sm btn-primary" href="submodules.php?edit=<?= (int)$s['submodule_id'] ?>">Move/Edit</a>
            <form method="post" onsubmit="return confirm('Delete this submodule?')" style="margin:0">
              <input type="hidden" name="action" value="delete_sub">
              <input type="hidden" name="submodule_id" value="<?= (int)$s['submodule_id'] ?>">
              <button type="submit" class="btn btn-sm btn-danger">Delete</button>
            </form>
          </div>
        </td>
      </tr>
      <?php endwhile; ?>
    </tbody>
  </table>
  <?php endif; ?>
</div>

<div id="addModal" class="modal" onclick="if(event.target===this)closeModal(this)">
  <div class="modal-card">
    <h4>Create Submodule</h4>
    <form method="post">
      <input type="hidden" name="action" value="add_sub">
      <div class="field"><label>Parent Module</label>
        <select name="module_id" required>
          <?php mysqli_data_seek($modules, 0); while ($m = mysqli_fetch_assoc($modules)): ?>
          <option value="<?= (int)$m['module_id'] ?>" <?= $filterModule === (int)$m['module_id'] ? 'selected' : '' ?>><?= sa_es($m['module_name']) ?></option>
          <?php endwhile; ?>
        </select>
      </div>
      <div class="row">
        <div class="field"><label>Key (slug)</label><input type="text" name="submodule_key" required placeholder="e.g. exam_scheduling"></div>
        <div class="field"><label>Name</label><input type="text" name="submodule_name" required placeholder="e.g. Exam Scheduling"></div>
      </div>
      <div class="field"><label>Route / Target</label><input type="text" name="route" placeholder="/uni-mis-project/..."></div>
      <div class="field"><label>Description</label><input type="text" name="description"></div>
      <div class="field"><label>Sort Order</label><input type="number" name="sort_order" value="0"></div>
      <div class="flex jc-between mt-3">
        <button type="button" class="btn" onclick="closeModal(this)">Cancel</button>
        <button type="submit" class="btn btn-primary">Create</button>
      </div>
    </form>
  </div>
</div>

<?php if ($editing): ?>
<div id="editModal" class="modal open" onclick="if(event.target===this)closeModal(this)">
  <div class="modal-card">
    <h4>Edit / Move Submodule #<?= (int)$editing['submodule_id'] ?> — <?= sa_es($editing['submodule_name']) ?></h4>
    <form method="post">
      <input type="hidden" name="action" value="update_sub">
      <input type="hidden" name="submodule_id" value="<?= (int)$editing['submodule_id'] ?>">
      <div class="field"><label>Parent Module (move here)</label>
        <select name="module_id" required>
          <?php mysqli_data_seek($modules, 0); while ($m = mysqli_fetch_assoc($modules)): ?>
          <option value="<?= (int)$m['module_id'] ?>" <?= (int)$editing['module_id'] === (int)$m['module_id'] ? 'selected' : '' ?>><?= sa_es($m['module_name']) ?></option>
          <?php endwhile; ?>
        </select>
      </div>
      <div class="row">
        <div class="field"><label>Key</label><input type="text" name="submodule_key" value="<?= sa_es($editing['submodule_key']) ?>" required></div>
        <div class="field"><label>Name</label><input type="text" name="submodule_name" value="<?= sa_es($editing['submodule_name']) ?>" required></div>
      </div>
      <div class="field"><label>Route</label><input type="text" name="route" value="<?= sa_es($editing['route'] ?? '') ?>"></div>
      <div class="field"><label>Description</label><input type="text" name="description" value="<?= sa_es($editing['description'] ?? '') ?>"></div>
      <div class="row">
        <div class="field"><label>Sort</label><input type="number" name="sort_order" value="<?= (int)$editing['sort_order'] ?>"></div>
        <div class="field"><label>Status</label>
          <select name="status">
            <option value="Active" <?= $editing['status'] === 'Active' ? 'selected' : '' ?>>Active</option>
            <option value="Inactive" <?= $editing['status'] !== 'Active' ? 'selected' : '' ?>>Inactive</option>
          </select>
        </div>
      </div>
      <div class="flex jc-between mt-3">
        <a class="btn" href="submodules.php?module_id=<?= $filterModule ?>">Cancel</a>
        <button type="submit" class="btn btn-primary">Save</button>
      </div>
    </form>
  </div>
</div>
<?php endif; ?>

<script>
function openModal(id){document.getElementById(id).classList.add('open')}
function closeModal(el){el.closest('.modal').classList.remove('open')}
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
