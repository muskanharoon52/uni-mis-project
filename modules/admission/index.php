<?php
require_once __DIR__ . '/config/database.php';
$page_title = 'Dashboard';
include __DIR__ . '/includes/header.php';

try {
    $stats['applications']  = $pdo->query("SELECT COUNT(*) FROM admission_applications")->fetchColumn();
    $stats['pending']       = $pdo->query("SELECT COUNT(*) FROM admission_applications WHERE application_status IN ('Submitted', 'Under Review')")->fetchColumn();
    $stats['approved']      = $pdo->query("SELECT COUNT(*) FROM admission_applications WHERE application_status IN ('Approved', 'Admitted')")->fetchColumn();
    $stats['rejected']      = $pdo->query("SELECT COUNT(*) FROM admission_applications WHERE application_status = 'Rejected'")->fetchColumn();
    $stats['students']      = $pdo->query("SELECT COUNT(*) FROM admission_students")->fetchColumn();
    $stats['departments']   = $pdo->query("SELECT COUNT(*) FROM departments")->fetchColumn();

    try { $stats['scholarships'] = $pdo->query("SELECT COUNT(*) FROM admission_scholarships WHERE status='active'")->fetchColumn(); }
    catch (Exception $e) { $stats['scholarships'] = 0; }

    try { $stats['fees_paid'] = $pdo->query("SELECT COUNT(*) FROM admission_fee_payments WHERE status='paid'")->fetchColumn(); }
    catch (Exception $e) { $stats['fees_paid'] = 0; }

    $recent = $pdo->query("
        SELECT a.*, d.department_name
        FROM admission_applications a
        LEFT JOIN departments d ON a.program_id = d.department_id
        ORDER BY a.application_id DESC LIMIT 6
    ")->fetchAll();

    $status_rows = $pdo->query("
        SELECT application_status, COUNT(*) as cnt
        FROM admission_applications
        GROUP BY application_status
    ")->fetchAll(PDO::FETCH_KEY_PAIR);

    try {
        $recent_students = $pdo->query("
            SELECT s.*, d.department_name
            FROM admission_students s
            LEFT JOIN departments d ON s.program_id = d.department_id
            ORDER BY s.id DESC LIMIT 5
        ")->fetchAll();
    } catch (Exception $e) { $recent_students = []; }

    try {
        $active_scholarships = $pdo->query("
            SELECT * FROM admission_scholarships WHERE status='active' ORDER BY id DESC LIMIT 4
        ")->fetchAll();
    } catch (Exception $e) { $active_scholarships = []; }

} catch (PDOException $e) {
    $stats = ['applications'=>0,'pending'=>0,'approved'=>0,'rejected'=>0,'students'=>0,'departments'=>0,'scholarships'=>0,'fees_paid'=>0];
    $recent = []; $status_rows = []; $recent_students = []; $active_scholarships = [];
}

$hour = (int)date('H');
$greeting = $hour < 12 ? 'Good Morning' : ($hour < 17 ? 'Good Afternoon' : 'Good Evening');
$userName = $_SESSION['full_name'] ?? $_SESSION['user_name'] ?? 'Admin';
$firstName = explode(' ', trim($userName))[0];

$flash = getFlash();
if ($flash): ?>
    <div class="alert alert-<?= $flash['type'] === 'success' ? 'success' : 'error' ?>">
        <?= $flash['message'] ?>
    </div>
<?php endif; ?>

<!-- Hero Banner -->
<div class="greeting-card">
    <div class="greeting-card-body">
        <div style="font-size:.75rem;font-weight:600;text-transform:uppercase;letter-spacing:.1em;color:rgba(255,255,255,0.5);margin-bottom:6px;">Admission Management System</div>
        <h1 style="font-size:1.6rem;font-weight:800;color:#fff;margin:0 0 6px;"><?= $greeting ?>, <?= htmlspecialchars($firstName) ?>! 👋</h1>
        <p style="margin:0 0 16px;font-size:.88rem;color:rgba(255,255,255,0.65);">Here's your admissions overview for today. You have <strong style="color:#fff;"><?= $stats['pending'] ?> pending</strong> applications awaiting review.</p>
        <div style="display:flex;gap:10px;flex-wrap:wrap;">
            <a href="applications/add.php" class="btn btn-ghost" style="background:#fff;color:var(--accent);"><i class="fas fa-plus"></i> New Application</a>
            <a href="applications/index.php" class="btn btn-ghost" style="background:rgba(255,255,255,0.14);color:#fff;border:1px solid rgba(255,255,255,0.22);"><i class="fas fa-list"></i> All Applications</a>
        </div>
    </div>
    <div style="position:absolute;top:28px;right:32px;text-align:right;z-index:1;">
        <div style="font-size:2.8rem;font-weight:800;color:#fff;line-height:1;"><?= date('d') ?></div>
        <div style="font-size:.95rem;font-weight:600;color:rgba(255,255,255,0.75);"><?= date('M Y') ?></div>
        <div style="font-size:.75rem;color:rgba(255,255,255,0.45);margin-top:3px;"><?= date('l') ?></div>
        <div style="margin-top:12px;background:rgba(255,255,255,0.1);padding:7px 14px;border-radius:8px;font-size:.76rem;color:rgba(255,255,255,0.65);"><i class="fas fa-clock"></i> <?= date('h:i A') ?></div>
    </div>
</div>

<!-- Primary Stat Cards -->
<div class="stat-row">
    <div class="stat-card-v2" style="cursor:pointer;" onclick="location.href='applications/index.php'">
        <div class="stat-card-v2-icon" style="background:var(--accent-light);color:var(--accent);"><i class="fas fa-file-alt"></i></div>
        <div class="stat-card-v2-body">
            <div class="stat-card-v2-label">Total Applications</div>
            <div class="stat-card-v2-value"><?= $stats['applications'] ?></div>
            <div class="stat-card-v2-hint">All-time submissions</div>
        </div>
    </div>
    <div class="stat-card-v2" style="cursor:pointer;" onclick="location.href='applications/index.php'">
        <div class="stat-card-v2-icon" style="background:var(--warning-bg);color:var(--warning);"><i class="fas fa-hourglass-half"></i></div>
        <div class="stat-card-v2-body">
            <div class="stat-card-v2-label">Pending Review</div>
            <div class="stat-card-v2-value" style="color:var(--warning);"><?= $stats['pending'] ?></div>
            <div class="stat-card-v2-hint">Awaiting action</div>
        </div>
    </div>
    <div class="stat-card-v2" style="cursor:pointer;" onclick="location.href='students/index.php'">
        <div class="stat-card-v2-icon" style="background:var(--success-bg);color:var(--success);"><i class="fas fa-user-graduate"></i></div>
        <div class="stat-card-v2-body">
            <div class="stat-card-v2-label">Enrolled Students</div>
            <div class="stat-card-v2-value" style="color:var(--success);"><?= $stats['students'] ?></div>
            <div class="stat-card-v2-hint">Active registrations</div>
        </div>
    </div>
    <div class="stat-card-v2" style="cursor:pointer;" onclick="location.href='scholarships/index.php'">
        <div class="stat-card-v2-icon" style="background:#F3E8FF;color:#7c3aed;"><i class="fas fa-award"></i></div>
        <div class="stat-card-v2-body">
            <div class="stat-card-v2-label">Active Scholarships</div>
            <div class="stat-card-v2-value" style="color:#7c3aed;"><?= $stats['scholarships'] ?></div>
            <div class="stat-card-v2-hint">Open programmes</div>
        </div>
    </div>
</div>

<!-- Secondary Stat Row -->
<div class="stat-row">
    <div class="stat-card-v2">
        <div class="stat-card-v2-icon" style="background:var(--success-bg);color:var(--success);"><i class="fas fa-check-circle"></i></div>
        <div class="stat-card-v2-body">
            <div class="stat-card-v2-label">Approved</div>
            <div class="stat-card-v2-value" style="color:var(--success);"><?= $stats['approved'] ?></div>
        </div>
    </div>
    <div class="stat-card-v2">
        <div class="stat-card-v2-icon" style="background:var(--danger-bg);color:var(--danger);"><i class="fas fa-times-circle"></i></div>
        <div class="stat-card-v2-body">
            <div class="stat-card-v2-label">Rejected</div>
            <div class="stat-card-v2-value" style="color:var(--danger);"><?= $stats['rejected'] ?></div>
        </div>
    </div>
    <div class="stat-card-v2">
        <div class="stat-card-v2-icon" style="background:var(--accent-light);color:var(--accent);"><i class="fas fa-building"></i></div>
        <div class="stat-card-v2-body">
            <div class="stat-card-v2-label">Departments</div>
            <div class="stat-card-v2-value"><?= $stats['departments'] ?></div>
        </div>
    </div>
    <div class="stat-card-v2">
        <div class="stat-card-v2-icon" style="background:var(--success-bg);color:var(--success);"><i class="fas fa-credit-card"></i></div>
        <div class="stat-card-v2-body">
            <div class="stat-card-v2-label">Fees Paid</div>
            <div class="stat-card-v2-value"><?= $stats['fees_paid'] ?></div>
        </div>
    </div>
</div>

<!-- Quick Actions -->
<div class="card" style="margin-bottom:24px;">
    <div class="card-header">
        <div><h3>Quick Actions</h3><p>Common tasks and module shortcuts</p></div>
    </div>
    <div class="card-content">
        <div style="display:grid;grid-template-columns:repeat(6,1fr);gap:12px;">
            <a href="applications/add.php" class="btn btn-primary" style="justify-content:center;"><i class="fas fa-file-plus"></i><span style="margin-top:4px;">New Application</span></a>
            <a href="students/add.php" class="btn btn-success" style="justify-content:center;"><i class="fas fa-user-plus"></i><span style="margin-top:4px;">Add Student</span></a>
            <a href="scholarships/add.php" class="btn btn-primary" style="justify-content:center;"><i class="fas fa-award"></i><span style="margin-top:4px;">Add Scholarship</span></a>
            <a href="fees/index.php" class="btn btn-primary" style="justify-content:center;"><i class="fas fa-money-bill-wave"></i><span style="margin-top:4px;">Fee Management</span></a>
            <a href="reports/index.php" class="btn btn-danger" style="justify-content:center;"><i class="fas fa-chart-bar"></i><span style="margin-top:4px;">Reports</span></a>
            <a href="settings/index.php" class="btn btn-outline" style="justify-content:center;"><i class="fas fa-cog"></i><span style="margin-top:4px;">Settings</span></a>
        </div>
    </div>
</div>

<!-- Recent Applications Table -->
<div class="card" style="margin-bottom:24px;">
    <div class="card-header">
        <div><h3>Recent Applications</h3><p>Latest admission submissions</p></div>
        <a href="applications/index.php" class="btn btn-sm btn-outline">View All <i class="fas fa-arrow-right"></i></a>
    </div>
    <div class="card-content">
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Application #</th>
                        <th>Student Name</th>
                        <th>Department</th>
                        <th>Date Submitted</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($recent)): ?>
                        <tr><td colspan="6">
                            <div class="empty-state">
                                <i class="fas fa-inbox"></i>
                                <h5>No Applications Yet</h5>
                                <p>Start by submitting a new application</p>
                            </div>
                        </td></tr>
                    <?php else: foreach($recent as $app): ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($app['temp_application_no'] ?? '#' . ($app['application_id'] ?? 'N/A')) ?></strong></td>
                        <td>
                            <div style="font-weight:600;"><?= htmlspecialchars($app['full_name'] ?? 'N/A') ?></div>
                            <?php if (!empty($app['email'])): ?><div style="font-size:.75rem;color:var(--muted);"><?= htmlspecialchars($app['email']) ?></div><?php endif; ?>
                        </td>
                        <td style="font-size:.84rem;"><?= htmlspecialchars($app['department_name'] ?? 'N/A') ?></td>
                        <td style="font-size:.84rem;"><?= isset($app['submitted_at']) ? date('d M Y', strtotime($app['submitted_at'])) : (isset($app['created_at']) ? date('d M Y', strtotime($app['created_at'])) : 'N/A') ?></td>
                        <td><span class="status-badge <?= htmlspecialchars($app['application_status'] ?? 'Submitted') ?>"><?= htmlspecialchars($app['application_status'] ?? 'Submitted') ?></span></td>
                        <td>
                            <a href="applications/view.php?id=<?= $app['application_id'] ?? 0 ?>" class="btn btn-sm btn-outline"><i class="fas fa-eye"></i> View</a>
                            <?php if (($app['application_status'] ?? '') == 'Submitted'): ?>
                                <a href="applications/review.php?id=<?= $app['application_id'] ?? 0 ?>" class="btn btn-sm btn-primary"><i class="fas fa-check"></i> Review</a>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Status Breakdown + Recent Students -->
<div class="grid-2" style="margin-bottom:24px;">

    <!-- Status Breakdown -->
    <div class="card">
        <div class="card-header">
            <div><h3>Application Status Breakdown</h3><p>Distribution across decision stages</p></div>
        </div>
        <div class="card-content">
            <?php
            $total = max(1, (int)$stats['applications']);
            $statusConfig = [
                'Submitted'    => ['color'=>'#2563EB','bg'=>'#eff6ff','label'=>'Submitted','icon'=>'fa-paper-plane'],
                'Under Review' => ['color'=>'#f59e0b','bg'=>'#fffbeb','label'=>'Under Review','icon'=>'fa-search'],
                'Approved'     => ['color'=>'#10b981','bg'=>'#f0fdf4','label'=>'Approved','icon'=>'fa-check-circle'],
                'Admitted'     => ['color'=>'#8b5cf6','bg'=>'#faf5ff','label'=>'Admitted','icon'=>'fa-graduation-cap'],
                'Rejected'     => ['color'=>'#ef4444','bg'=>'#fff1f2','label'=>'Rejected','icon'=>'fa-times-circle'],
            ];
            if (empty($status_rows)): ?>
                <div class="empty-state" style="padding:30px 0;">
                    <i class="fas fa-chart-pie"></i><h5>No Data Yet</h5>
                    <p>Status breakdown will appear once applications are submitted</p>
                </div>
            <?php else:
                foreach ($statusConfig as $key => $cfg):
                    $count = (int)($status_rows[$key] ?? 0);
                    if ($count === 0) continue;
                    $pct = round(($count / $total) * 100);
            ?>
                <div style="margin-bottom:16px;">
                    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:6px;">
                        <div style="display:flex;align-items:center;gap:9px;">
                            <div style="width:30px;height:30px;border-radius:8px;background:<?= $cfg['bg'] ?>;display:grid;place-items:center;color:<?= $cfg['color'] ?>;font-size:12px;"><i class="fas <?= $cfg['icon'] ?>"></i></div>
                            <span style="font-size:.84rem;font-weight:600;color:var(--text-strong);"><?= $cfg['label'] ?></span>
                        </div>
                        <div style="font-size:.84rem;font-weight:700;color:<?= $cfg['color'] ?>;"><?= $count ?> <span style="font-weight:400;color:var(--muted);font-size:.78rem;">(<?= $pct ?>%)</span></div>
                    </div>
                    <div style="background:var(--bg);border-radius:100px;height:7px;overflow:hidden;">
                        <div style="height:100%;border-radius:100px;transition:width .5s ease;width:<?= $pct ?>%;background:<?= $cfg['color'] ?>;"></div>
                    </div>
                </div>
            <?php endforeach; endif; ?>
        </div>
    </div>

    <!-- Recently Enrolled Students -->
    <div class="card">
        <div class="card-header">
            <div><h3>Recently Enrolled Students</h3><p>Latest registrations in the system</p></div>
            <a href="students/index.php" class="btn btn-sm btn-outline">All</a>
        </div>
        <div class="card-content">
            <?php if (empty($recent_students)): ?>
                <div class="empty-state" style="padding:30px 0;">
                    <i class="fas fa-user-graduate"></i>
                    <h5>No Students Yet</h5>
                    <p>Students are enrolled when applications are approved</p>
                </div>
            <?php else: foreach($recent_students as $st): ?>
            <div style="display:flex;align-items:center;gap:12px;padding:10px 0;border-bottom:1px solid var(--border);">
                <div style="width:38px;height:38px;border-radius:50%;flex-shrink:0;display:grid;place-items:center;font-weight:700;font-size:.9rem;color:#fff;background:linear-gradient(135deg,#2563EB,#8b5cf6);"><?= strtoupper(substr($st['student_name'] ?? 'S', 0, 1)) ?></div>
                <div style="flex:1;min-width:0;">
                    <div style="font-weight:600;font-size:.87rem;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;"><?= htmlspecialchars($st['student_name'] ?? 'N/A') ?></div>
                    <div style="font-size:.75rem;color:var(--muted);"><?= htmlspecialchars($st['student_id'] ?? '') ?> &nbsp;·&nbsp; <?= htmlspecialchars($st['department_name'] ?? 'N/A') ?></div>
                </div>
                <span class="status-badge <?= strtolower($st['status'] ?? 'active') ?>"><?= ucfirst($st['status'] ?? 'Active') ?></span>
            </div>
            <?php endforeach; endif; ?>
        </div>
    </div>
</div>

<!-- Active Scholarships -->
<?php if (!empty($active_scholarships)): ?>
<div class="card" style="margin-bottom:24px;">
    <div class="card-header">
        <div><h3>Active Scholarship Programmes</h3><p>Currently open for student applications</p></div>
        <a href="scholarships/index.php" class="btn btn-sm btn-outline">Manage</a>
    </div>
    <div class="card-content">
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr><th>Scholarship Name</th><th>Type</th><th>Coverage</th><th>Min Marks %</th><th>Deadline</th><th>Action</th></tr>
                </thead>
                <tbody>
                    <?php foreach($active_scholarships as $sch): ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($sch['scholarship_name']) ?></strong></td>
                        <td><span class="badge badge-student"><?= htmlspecialchars($sch['scholarship_type'] ?? 'Merit') ?></span></td>
                        <td><strong style="color:#7c3aed;"><?= number_format($sch['scholarship_percentage'] ?? 0, 0) ?>%</strong> off</td>
                        <td><?= number_format($sch['min_marks_percentage'] ?? 0, 0) ?>% minimum</td>
                        <td style="font-size:.83rem;"><?= isset($sch['deadline']) ? date('d M Y', strtotime($sch['deadline'])) : 'Open' ?></td>
                        <td><a href="scholarships/apply.php?scholarship_id=<?= $sch['id'] ?>" class="btn btn-sm btn-outline"><i class="fas fa-hand-holding-heart"></i> Apply</a></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php endif; ?>

<?php include __DIR__ . '/includes/footer.php'; ?>