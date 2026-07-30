<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/database.php';
$page_title = 'Applications';
include __DIR__ . '/../includes/header.php';

// Get admission stats from the database
try {
    $stats_query = "SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN status = 'pending' OR application_status = 'pending' 
                 OR application_status = 'Submitted' OR application_status = 'Under Review' 
                 THEN 1 ELSE 0 END) as pending,
        SUM(CASE WHEN status = 'approved' OR application_status = 'approved' 
                 OR application_status = 'Approved' OR application_status = 'Admitted' 
                 THEN 1 ELSE 0 END) as approved,
        SUM(CASE WHEN status = 'rejected' OR application_status = 'rejected' 
                 OR application_status = 'Rejected' 
                 THEN 1 ELSE 0 END) as rejected
        FROM admission_applications";
    
    $stats_result = $pdo->query($stats_query);
    $stats = $stats_result->fetch(PDO::FETCH_ASSOC);
    
    // Default values if no data
    if (!$stats) {
        $stats = ['total' => 0, 'pending' => 0, 'approved' => 0, 'rejected' => 0];
    }
} catch (PDOException $e) {
    $stats = ['total' => 0, 'pending' => 0, 'approved' => 0, 'rejected' => 0];
}

// Fetch all applications
$apps = $pdo->query("
    SELECT a.*, d.department_name 
    FROM admission_applications a 
    LEFT JOIN departments d ON a.program_id = d.department_id 
    ORDER BY a.application_id DESC
")->fetchAll();

$flash = getFlash();
if ($flash): ?>
    <div class="alert alert-<?= $flash['type'] === 'success' ? 'success' : 'danger' ?>">
        <i class="fas fa-<?= $flash['type'] === 'success' ? 'check-circle' : 'exclamation-circle' ?>"></i>
        <?= $flash['message'] ?>
    </div>
<?php endif; ?>

<!-- ADMISSION STATS BAR -->
<div class="admission-stats-bar" style="display:grid;grid-template-columns:repeat(4,1fr);gap:15px;margin-bottom:25px;padding:20px;background:#f8fafc;border-radius:12px;border:1px solid #e2e8f0;">
    <div style="text-align:center;padding:10px;">
        <div style="font-size:28px;font-weight:700;color:#2563eb;"><?= number_format($stats['total'] ?? 0) ?></div>
        <div style="font-size:13px;color:#64748b;margin-top:4px;">
            <i class="fas fa-file-alt" style="color:#2563eb;font-size:12px;"></i> Total Applications
        </div>
    </div>
    <div style="text-align:center;padding:10px;border-left:1px solid #e2e8f0;">
        <div style="font-size:28px;font-weight:700;color:#f59e0b;"><?= number_format($stats['pending'] ?? 0) ?></div>
        <div style="font-size:13px;color:#64748b;margin-top:4px;">
            <i class="fas fa-hourglass-half" style="color:#f59e0b;font-size:12px;"></i> Pending Review
        </div>
    </div>
    <div style="text-align:center;padding:10px;border-left:1px solid #e2e8f0;">
        <div style="font-size:28px;font-weight:700;color:#10b981;"><?= number_format($stats['approved'] ?? 0) ?></div>
        <div style="font-size:13px;color:#64748b;margin-top:4px;">
            <i class="fas fa-check-circle" style="color:#10b981;font-size:12px;"></i> Approved
        </div>
    </div>
    <div style="text-align:center;padding:10px;border-left:1px solid #e2e8f0;">
        <div style="font-size:28px;font-weight:700;color:#ef4444;"><?= number_format($stats['rejected'] ?? 0) ?></div>
        <div style="font-size:13px;color:#64748b;margin-top:4px;">
            <i class="fas fa-times-circle" style="color:#ef4444;font-size:12px;"></i> Rejected
        </div>
    </div>
</div>

<!-- PAGE HEADER WITH ACTIONS -->
<div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:15px;margin-bottom:24px;">
    <div>
        <h4 style="margin:0;color:#111827;font-weight:600;">
            <i class="fas fa-file-alt" style="color:#2563eb;"></i> 
            Applications <span style="font-size:14px;font-weight:400;color:#6b7280;">(<?= count($apps) ?> total)</span>
        </h4>
        <p style="margin:4px 0 0 0;font-size:14px;color:#6b7280;">Manage all student admission applications</p>
    </div>
    <div style="display:flex;gap:10px;flex-wrap:wrap;">
        <a href="../admission/register.php" target="_blank" style="display:inline-flex;align-items:center;gap:6px;background:#10b981;color:#fff;padding:8px 18px;border-radius:8px;text-decoration:none;font-size:14px;font-weight:500;transition:all 0.3s;border:none;">
            <i class="fas fa-globe"></i> Public Registration
        </a>
        <a href="add.php" style="display:inline-flex;align-items:center;gap:6px;background:#2563eb;color:#fff;padding:8px 18px;border-radius:8px;text-decoration:none;font-size:14px;font-weight:500;transition:all 0.3s;border:none;">
            <i class="fas fa-plus"></i> New Application
        </a>
    </div>
</div>

<!-- MAIN CARD -->
<div class="card">
    <div class="card-header">
        <div>
            <h3><i class="fas fa-list-ul" style="color:#2563eb;"></i> Applications Directory</h3>
            <p>List of all student admission applications</p>
        </div>
        <div style="display:flex;gap:8px;">
            <input type="text" id="searchApp" placeholder="🔍 Search applications..." style="padding:6px 12px;border:1px solid #e2e8f0;border-radius:6px;font-size:13px;">
            <select id="filterStatus" style="padding:6px 12px;border:1px solid #e2e8f0;border-radius:6px;font-size:13px;">
                <option value="">All Status</option>
                <option value="pending">Pending</option>
                <option value="submitted">Submitted</option>
                <option value="under review">Under Review</option>
                <option value="approved">Approved</option>
                <option value="admitted">Admitted</option>
                <option value="rejected">Rejected</option>
            </select>
        </div>
    </div>
    <div class="card-content">
        <?php if (empty($apps)): ?>
            <div class="empty-state" style="text-align:center;padding:60px 20px;">
                <i class="fas fa-inbox" style="font-size:48px;color:#d1d5db;margin-bottom:16px;"></i>
                <h5 style="color:#4b5563;margin-bottom:8px;">No Applications Found</h5>
                <p style="color:#6b7280;font-size:14px;">Start by adding a new student admission application.</p>
                <div style="margin-top:16px;display:flex;gap:10px;justify-content:center;">
                    <a href="add.php" class="btn btn-primary" style="background:#2563eb;color:#fff;padding:10px 24px;border-radius:8px;text-decoration:none;">
                        <i class="fas fa-plus"></i> New Application
                    </a>
                    <a href="../admission/register.php" target="_blank" class="btn btn-success" style="background:#10b981;color:#fff;padding:10px 24px;border-radius:8px;text-decoration:none;">
                        <i class="fas fa-globe"></i> Public Registration
                    </a>
                </div>
            </div>
        <?php else: ?>
            <div class="table-responsive" style="overflow-x:auto;">
                <table class="data-table" id="applicationsTable" style="width:100%;border-collapse:collapse;font-size:14px;">
                    <thead>
                        <tr style="border-bottom:2px solid #e5e7eb;background:#f9fafb;">
                            <th style="text-align:left;padding:12px 10px;font-weight:600;color:#4b5563;">#</th>
                            <th style="text-align:left;padding:12px 10px;font-weight:600;color:#4b5563;">Application #</th>
                            <th style="text-align:left;padding:12px 10px;font-weight:600;color:#4b5563;">Student Name</th>
                            <th style="text-align:left;padding:12px 10px;font-weight:600;color:#4b5563;">Father Name</th>
                            <th style="text-align:left;padding:12px 10px;font-weight:600;color:#4b5563;">Department</th>
                            <th style="text-align:left;padding:12px 10px;font-weight:600;color:#4b5563;">Date</th>
                            <th style="text-align:left;padding:12px 10px;font-weight:600;color:#4b5563;">Status</th>
                            <th style="text-align:left;padding:12px 10px;font-weight:600;color:#4b5563;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $counter = 1;
                        foreach($apps as $app): 
                            // Determine the status
                            $status = $app['application_status'] ?? $app['status'] ?? 'Submitted';
                            $status_lower = strtolower($status);
                            
                            // Set badge class
                            $badge_class = 'status-badge';
                            if (in_array($status_lower, ['submitted', 'pending', 'under review'])) {
                                $badge_class .= ' pending';
                            } elseif (in_array($status_lower, ['approved', 'admitted'])) {
                                $badge_class .= ' approved';
                            } elseif ($status_lower == 'rejected') {
                                $badge_class .= ' rejected';
                            }
                            
                            // Get date
                            $date = $app['submitted_at'] ?? $app['created_at'] ?? $app['applied_date'] ?? null;
                            $date_formatted = $date ? date('d M Y', strtotime($date)) : 'N/A';
                            
                            // Get application ID
                            $app_id = $app['application_id'] ?? $app['id'] ?? 0;
                        ?>
                        <tr style="border-bottom:1px solid #f3f4f6;transition:background 0.2s;" 
                            onmouseover="this.style.background='#f8fafc'" 
                            onmouseout="this.style.background='transparent'">
                            <td style="padding:12px 10px;color:#6b7280;font-size:13px;"><?= $counter++ ?></td>
                            <td style="padding:12px 10px;">
                                <strong style="color:#1a1a2e;"><?= htmlspecialchars($app['temp_application_no'] ?? $app['application_id'] ?? 'N/A') ?></strong>
                            </td>
                            <td style="padding:12px 10px;">
                                <div style="font-weight:600;color:#111827;"><?= htmlspecialchars($app['full_name'] ?? 'N/A') ?></div>
                                <?php if (!empty($app['email'])): ?>
                                    <div style="font-size:12px;color:#6b7280;"><?= htmlspecialchars($app['email']) ?></div>
                                <?php endif; ?>
                            </td>
                            <td style="padding:12px 10px;color:#4b5563;"><?= htmlspecialchars($app['father_name'] ?? 'N/A') ?></td>
                            <td style="padding:12px 10px;">
                                <span style="background:#eff6ff;color:#2563eb;padding:2px 10px;border-radius:12px;font-size:12px;">
                                    <?= htmlspecialchars($app['department_name'] ?? $app['program'] ?? 'N/A') ?>
                                </span>
                            </td>
                            <td style="padding:12px 10px;font-size:13px;color:#6b7280;"><?= $date_formatted ?></td>
                            <td style="padding:12px 10px;">
                                <span class="<?= $badge_class ?>"><?= htmlspecialchars(ucfirst($status)) ?></span>
                            </td>
                            <td style="padding:12px 10px;">
                                <div style="display:flex;gap:6px;flex-wrap:wrap;">
                                    <a href="view.php?id=<?= $app_id ?>" 
                                       style="display:inline-flex;align-items:center;gap:4px;padding:4px 12px;background:#f3f4f6;color:#4b5563;border-radius:6px;text-decoration:none;font-size:12px;transition:all 0.2s;"
                                       onmouseover="this.style.background='#e5e7eb'"
                                       onmouseout="this.style.background='#f3f4f6'">
                                        <i class="fas fa-eye"></i> View
                                    </a>
                                    <?php if(in_array($status_lower, ['submitted', 'pending', 'under review'])): ?>
                                    <a href="review.php?id=<?= $app_id ?>" 
                                       style="display:inline-flex;align-items:center;gap:4px;padding:4px 12px;background:#2563eb;color:#fff;border-radius:6px;text-decoration:none;font-size:12px;transition:all 0.2s;"
                                       onmouseover="this.style.background='#1d4ed8'"
                                       onmouseout="this.style.background='#2563eb'">
                                        <i class="fas fa-check"></i> Review
                                    </a>
                                    <?php endif; ?>
                                    <?php if(in_array($status_lower, ['approved', 'admitted'])): ?>
                                    <span style="display:inline-flex;align-items:center;gap:4px;padding:4px 12px;background:#f0fdf4;color:#10b981;border-radius:6px;font-size:12px;">
                                        <i class="fas fa-check-circle"></i> Processed
                                    </span>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Table Footer -->
            <div style="display:flex;justify-content:space-between;align-items:center;padding:12px 0 0 0;border-top:1px solid #e5e7eb;margin-top:12px;">
                <div style="font-size:13px;color:#6b7280;">
                    <i class="fas fa-info-circle"></i> Showing <?= count($apps) ?> applications
                </div>
                <div style="display:flex;gap:6px;">
                    <button class="btn btn-sm btn-outline" onclick="window.location.href='../admission/register.php'" style="padding:4px 14px;border:1px solid #e2e8f0;background:transparent;border-radius:6px;cursor:pointer;font-size:12px;">
                        <i class="fas fa-globe"></i> Public Form
                    </button>
                    <button class="btn btn-sm btn-primary" onclick="window.location.href='add.php'" style="padding:4px 14px;background:#2563eb;color:#fff;border:none;border-radius:6px;cursor:pointer;font-size:12px;">
                        <i class="fas fa-plus"></i> Add New
                    </button>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- JavaScript for Search and Filter -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('searchApp');
    const filterSelect = document.getElementById('filterStatus');
    const table = document.getElementById('applicationsTable');
    
    if (!table) return;
    
    const rows = table.querySelectorAll('tbody tr');
    
    function filterTable() {
        const searchTerm = searchInput.value.toLowerCase();
        const filterStatus = filterSelect.value.toLowerCase();
        
        rows.forEach(row => {
            const text = row.textContent.toLowerCase();
            const statusCell = row.querySelector('td:nth-child(7)');
            const status = statusCell ? statusCell.textContent.toLowerCase().trim() : '';
            
            let matchesSearch = text.includes(searchTerm);
            let matchesFilter = filterStatus === '' || status.includes(filterStatus);
            
            row.style.display = (matchesSearch && matchesFilter) ? '' : 'none';
        });
    }
    
    if (searchInput) {
        searchInput.addEventListener('input', filterTable);
    }
    
    if (filterSelect) {
        filterSelect.addEventListener('change', filterTable);
    }
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>