<?php
$sa_page_title = 'Modules';
define('SA_INIT', true);
require_once __DIR__ . '/includes/auth.php';
sa_guard();
$conn = getConnection();
$me = (int)($_SESSION['user_id'] ?? 0);

$msg = ''; $err = '';

// Update SSO module name to "Student Services Office"
mysqli_query($conn, "UPDATE sa_modules SET module_name = 'Student Services Office (SSO)' WHERE module_key = 'sso' AND module_name = 'Single Sign-On (SSO)'");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add_module') {
        $key = strtolower(trim(preg_replace('/[^a-zA-Z0-9_-]/', '', $_POST['module_key'] ?? '')));
        $name = trim($_POST['module_name'] ?? '');
        $icon = trim($_POST['module_icon'] ?? '◫');
        $color = trim($_POST['module_color'] ?? '#6366f1');
        $sort = (int)($_POST['sort_order'] ?? 0);
        if ($key === '' || $name === '') {
            $err = 'Module key and name are required.';
        } else {
            $stmt = $conn->prepare("INSERT INTO sa_modules (module_key, module_name, module_icon, module_color, sort_order) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param('ssssi', $key, $name, $icon, $color, $sort);
            if ($stmt->execute()) {
                $mid = $stmt->insert_id;
                $msg = "Module '$name' created.";
                sa_log('CREATE_MODULE', 'module', $mid, "Created module '$name' (key: $key)");
            } else {
                $err = $conn->error;
            }
        }
    }

    if ($action === 'update_module') {
        $id = (int)($_POST['module_id'] ?? 0);
        $name = trim($_POST['module_name'] ?? '');
        $icon = trim($_POST['module_icon'] ?? '◫');
        $color = trim($_POST['module_color'] ?? '#6366f1');
        $sort = (int)($_POST['sort_order'] ?? 0);
        $status = $_POST['status'] === 'Active' ? 'Active' : 'Inactive';
        if ($id > 0 && $name !== '') {
            $stmt = $conn->prepare("UPDATE sa_modules SET module_name=?, module_icon=?, module_color=?, sort_order=?, status=? WHERE module_id=?");
            $stmt->bind_param('sssisi', $name, $icon, $color, $sort, $status, $id);
            if ($stmt->execute()) {
                $msg = "Module #$id updated.";
                sa_log('UPDATE_MODULE', 'module', $id, "Updated module '$name' (status: $status)");
            } else $err = $conn->error;
        } else $err = 'Invalid module.';
    }

    if ($action === 'delete_module') {
        $id = (int)($_POST['module_id'] ?? 0);
        $subs = (int)(mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) c FROM sa_submodules WHERE module_id=$id"))['c'] ?? 0);
        $rules = (int)(mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) c FROM sa_permissions WHERE module_id=$id"))['c'] ?? 0);
        if ($id > 0 && $subs === 0 && $rules === 0) {
            mysqli_query($conn, "DELETE FROM sa_modules WHERE module_id=$id");
            $msg = "Module #$id deleted.";
            sa_log('DELETE_MODULE', 'module', $id, "Deleted module #$id");
        } else {
            $err = "Cannot delete: module has $subs submodule(s) and $rules rule(s). Move or delete them first.";
        }
    }
}

$modules = mysqli_query($conn, "SELECT m.*, (SELECT COUNT(*) FROM sa_submodules s WHERE s.module_id=m.module_id) subs,
    (SELECT COUNT(*) FROM sa_permissions p WHERE p.module_id=m.module_id) rules
    FROM sa_modules m ORDER BY m.sort_order, m.module_id");

$editId = (int)($_GET['edit'] ?? 0);
$editing = null;
if ($editId > 0) {
    $editing = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM sa_modules WHERE module_id=$editId"));
}

$sa_active = 'modules';
include __DIR__ . '/includes/header.php';
?>

<div class="flex jc-between" style="margin-bottom:4px">
  <div>
    <div class="page-title">Module Registry</div>
    <div class="page-sub">Top-level modules governed by the Super Admin. Add, rename, recolor or deactivate any module.</div>
  </div>
  <button class="btn btn-primary" onclick="openModal('addModal')">+ New Module</button>
</div>

<?php if ($msg): ?><div class="alert ok"><?= sa_es($msg) ?></div><?php endif; ?>
<?php if ($err): ?><div class="alert err"><?= sa_es($err) ?></div><?php endif; ?>

<div class="module-grid">
  <?php while ($m = mysqli_fetch_assoc($modules)): ?>
  <div class="mod-card" style="--mc:<?= sa_es($m['module_color'] ?? '#6366f1') ?>">
    <div class="m-top">
      <div style="flex:1">
        <div class="m-name"><?= sa_es($m['module_name']) ?></div>
        <div class="m-key"><?= sa_es($m['module_key']) ?></div>
      </div>
      <span class="pill <?= $m['status'] === 'Active' ? 'g' : 'n' ?>"><?= sa_es($m['status']) ?></span>
    </div>
    <div class="m-stats">
      <span><b><?= (int)$m['subs'] ?></b> submodules</span>
      <span><b><?= (int)$m['rules'] ?></b> permission rules</span>
      <span>#<?= (int)$m['module_id'] ?></span>
    </div>
    <div class="m-actions">
      <a class="btn btn-sm btn-primary" href="submodules.php">Submodules</a>
      <a class="btn btn-sm" href="modules.php?edit=<?= (int)$m['module_id'] ?>">Edit</a>
      <form method="post" onsubmit="return confirm('Delete this module? Submodules & rules must be empty.')" style="margin:0">
        <input type="hidden" name="action" value="delete_module">
        <input type="hidden" name="module_id" value="<?= (int)$m['module_id'] ?>">
        <button type="submit" class="btn btn-sm btn-danger">Delete</button>
      </form>
    </div>
  </div>
  <?php endwhile; ?>
</div>

<div id="addModal" class="modal" onclick="if(event.target===this)closeModal(this)">
  <div class="modal-card">
    <h4>Create Module</h4>
    <form method="post">
      <input type="hidden" name="action" value="add_module">
      <div class="row">
        <div class="field"><label>Module Key (slug)</label><input type="text" name="module_key" required placeholder="e.g. admissions"></div>
        <div class="field"><label>Module Name</label><input type="text" name="module_name" required placeholder="e.g. Admissions"></div>
      </div>
      <div class="row">
        <div class="field"><label>Icon (emoji/text)</label><input type="text" name="module_icon" value="◫"></div>
        <div class="field"><label>Color (hex)</label><input type="text" name="module_color" value="#6366f1"></div>
        <div class="field"><label>Sort Order</label><input type="number" name="sort_order" value="0"></div>
      </div>
      <div class="flex jc-between mt-3">
        <button type="button" class="btn" onclick="closeModal(this)">Cancel</button>
        <button type="submit" class="btn btn-primary">Create Module</button>
      </div>
    </form>
  </div>
</div>

<?php if ($editing): ?>
<div id="editModal" class="modal open" onclick="if(event.target===this)closeModal(this)">
  <div class="modal-card">
    <h4>Edit Module #<?= (int)$editing['module_id'] ?> — <?= sa_es($editing['module_name']) ?></h4>
    <form method="post">
      <input type="hidden" name="action" value="update_module">
      <input type="hidden" name="module_id" value="<?= (int)$editing['module_id'] ?>">
      <div class="field"><label>Module Name</label><input type="text" name="module_name" value="<?= sa_es($editing['module_name']) ?>" required></div>
      <div class="row">
        <div class="field"><label>Icon</label><input type="text" name="module_icon" value="<?= sa_es($editing['module_icon']) ?>"></div>
        <div class="field"><label>Color</label><input type="text" name="module_color" value="<?= sa_es($editing['module_color']) ?>"></div>
        <div class="field"><label>Sort</label><input type="number" name="sort_order" value="<?= (int)$editing['sort_order'] ?>"></div>
      </div>
      <div class="field"><label>Status</label>
        <select name="status">
          <option value="Active" <?= $editing['status'] === 'Active' ? 'selected' : '' ?>>Active</option>
          <option value="Inactive" <?= $editing['status'] !== 'Active' ? 'selected' : '' ?>>Inactive</option>
        </select>
      </div>
      <div class="flex jc-between mt-3">
        <a class="btn" href="modules.php">Cancel</a>
        <button type="submit" class="btn btn-primary">Save Changes</button>
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