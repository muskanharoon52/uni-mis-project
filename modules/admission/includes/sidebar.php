<div class="sidebar">
    <div class="brand">
        <h4><i class="fas fa-graduation-cap"></i> Admission</h4>
        <small>Management System</small>
    </div>
    <nav>
        <!-- Dashboard -->
        <a class="nav-link <?= basename(dirname($_SERVER['PHP_SELF'])) == 'dashboard' ? 'active' : '' ?>" 
           href="<?= BASE_URL ?>dashboard/">
            <i class="fas fa-home"></i> Dashboard
        </a>

        <!-- ============================================ -->
        <!-- ADMISSIONS SECTION (NEW - Collapsible)      -->
        <!-- ============================================ -->
        <div class="nav-section">
            <div class="nav-section-header" onclick="toggleSection('admissionSection')">
                <i class="fas fa-user-graduate"></i> 
                <span>Admissions</span>
                <i class="fas fa-chevron-down" id="admissionArrow" style="margin-left:auto;font-size:12px;"></i>
            </div>
            <div class="nav-section-content" id="admissionSection" style="display:block;">
                <!-- Public Registration (Opens in new tab) -->
                <a class="nav-link <?= basename(dirname($_SERVER['PHP_SELF'])) == 'register' ? 'active' : '' ?>" 
                   href="<?= BASE_URL ?>admission/register.php" target="_blank">
                    <i class="fas fa-globe" style="color:#10b981;"></i> 
                    <span style="color:#10b981;">Public Registration</span>
                    <span style="font-size:10px;background:#10b981;color:#fff;padding:1px 8px;border-radius:10px;margin-left:auto;">New</span>
                </a>
                
                <!-- All Applications -->
                <a class="nav-link <?= basename(dirname($_SERVER['PHP_SELF'])) == 'applications' ? 'active' : '' ?>" 
                   href="<?= BASE_URL ?>applications/">
                    <i class="fas fa-list"></i> All Applications
                </a>
                
                <!-- Add New Application (Admin) -->
                <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'add.php' ? 'active' : '' ?>" 
                   href="<?= BASE_URL ?>applications/add.php">
                    <i class="fas fa-plus-circle"></i> Add Application
                </a>
                
                <!-- Pending Applications (Filtered) -->
                <a class="nav-link" 
                   href="<?= BASE_URL ?>applications/index.php?status=pending">
                    <i class="fas fa-clock" style="color:#f59e0b;"></i> 
                    Pending Reviews
                    <?php
                    // Get pending count
                    try {
                        $pending_count = $pdo->query("
                            SELECT COUNT(*) FROM admission_applications 
                            WHERE application_status IN ('Submitted', 'Under Review', 'pending')
                        ")->fetchColumn();
                        if($pending_count > 0): ?>
                            <span style="background:#f59e0b;color:#fff;font-size:10px;padding:1px 8px;border-radius:10px;margin-left:auto;">
                                <?= $pending_count ?>
                            </span>
                        <?php endif;
                    } catch(Exception $e) {}
                    ?>
                </a>
            </div>
        </div>

        <!-- ============================================ -->
        <!-- EXISTING STUDENTS SECTION                    -->
        <!-- ============================================ -->
        <a class="nav-link <?= basename(dirname($_SERVER['PHP_SELF'])) == 'students' ? 'active' : '' ?>" 
           href="<?= BASE_URL ?>students/">
            <i class="fas fa-users"></i> Students
        </a>

        <!-- ============================================ -->
        <!-- EXISTING FEES SECTION                        -->
        <!-- ============================================ -->
        <a class="nav-link <?= basename(dirname($_SERVER['PHP_SELF'])) == 'fees' ? 'active' : '' ?>" 
           href="<?= BASE_URL ?>fees/">
            <i class="fas fa-money-bill-wave"></i> Fees
        </a>

        <!-- ============================================ -->
        <!-- EXISTING SCHOLARSHIPS SECTION                -->
        <!-- ============================================ -->
        <a class="nav-link <?= basename(dirname($_SERVER['PHP_SELF'])) == 'scholarships' ? 'active' : '' ?>" 
           href="<?= BASE_URL ?>scholarships/">
            <i class="fas fa-trophy"></i> Scholarships
        </a>

        <!-- ============================================ -->
        <!-- EXISTING REPORTS SECTION                     -->
        <!-- ============================================ -->
        <a class="nav-link <?= basename(dirname($_SERVER['PHP_SELF'])) == 'reports' ? 'active' : '' ?>" 
           href="<?= BASE_URL ?>reports/">
            <i class="fas fa-chart-bar"></i> Reports
        </a>

        <!-- ============================================ -->
        <!-- SETTINGS SECTION (New)                       -->
        <!-- ============================================ -->
        <a class="nav-link <?= basename(dirname($_SERVER['PHP_SELF'])) == 'settings' ? 'active' : '' ?>" 
           href="<?= BASE_URL ?>settings/">
            <i class="fas fa-cog"></i> Settings
        </a>
    </nav>

    <!-- User Info & Logout -->
    <div class="sidebar-footer">
        <div class="user-info">
            <i class="fas fa-user-circle"></i> 
            <?= $_SESSION['full_name'] ?? $_SESSION['user_name'] ?? 'User' ?>
            <br>
            <small>
                <i class="fas fa-sign-out-alt"></i> 
                <a href="<?= BASE_URL ?>logout.php" style="color:var(--muted);text-decoration:none;">Logout</a>
            </small>
        </div>
    </div>
</div>

<!-- Sidebar Styles -->
<style>
/* ============================================ */
/* SIDEBAR STYLES                              */
/* ============================================ */
.sidebar {
    width: 260px;
    height: 100vh;
    position: fixed;
    left: 0;
    top: 0;
    background: #1a1a2e;
    color: #fff;
    display: flex;
    flex-direction: column;
    overflow-y: auto;
    z-index: 1000;
    transition: transform 0.3s ease;
    box-shadow: 2px 0 10px rgba(0,0,0,0.1);
}

/* Brand */
.sidebar .brand {
    padding: 24px 20px 16px;
    border-bottom: 1px solid rgba(255,255,255,0.08);
    flex-shrink: 0;
}

.sidebar .brand h4 {
    margin: 0;
    font-size: 20px;
    font-weight: 700;
    color: #fff;
}

.sidebar .brand h4 i {
    color: #4f8cf7;
    margin-right: 10px;
}

.sidebar .brand small {
    display: block;
    font-size: 11px;
    color: rgba(255,255,255,0.4);
    margin-top: 2px;
    letter-spacing: 0.5px;
    text-transform: uppercase;
}

/* Navigation */
.sidebar nav {
    flex: 1;
    padding: 16px 12px;
    overflow-y: auto;
}

/* Nav Links */
.sidebar .nav-link {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 10px 14px;
    border-radius: 8px;
    color: rgba(255,255,255,0.6);
    text-decoration: none;
    font-size: 14px;
    font-weight: 500;
    transition: all 0.2s ease;
    margin-bottom: 2px;
}

.sidebar .nav-link i {
    width: 18px;
    font-size: 15px;
    text-align: center;
    flex-shrink: 0;
}

.sidebar .nav-link:hover {
    background: rgba(255,255,255,0.08);
    color: #fff;
}

.sidebar .nav-link.active {
    background: rgba(79, 140, 247, 0.15);
    color: #4f8cf7;
}

.sidebar .nav-link.active i {
    color: #4f8cf7;
}

/* Nav Section */
.sidebar .nav-section {
    margin-top: 8px;
}

.sidebar .nav-section-header {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 10px 14px;
    border-radius: 8px;
    color: rgba(255,255,255,0.4);
    text-decoration: none;
    font-size: 13px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    cursor: pointer;
    transition: all 0.2s ease;
    user-select: none;
}

.sidebar .nav-section-header:hover {
    color: rgba(255,255,255,0.7);
}

.sidebar .nav-section-header i:first-child {
    width: 18px;
    font-size: 14px;
    text-align: center;
}

.sidebar .nav-section-content {
    padding-left: 12px;
    margin-top: 4px;
}

.sidebar .nav-section-content .nav-link {
    padding-left: 44px;
    font-size: 13px;
    font-weight: 400;
}

/* User Info & Logout */
.sidebar-footer {
    border-top: 1px solid rgba(255,255,255,0.08);
    padding: 16px 20px;
    flex-shrink: 0;
}

.user-info {
    font-size: 14px;
    color: rgba(255,255,255,0.7);
}

.user-info i {
    margin-right: 8px;
}

.user-info small {
    display: block;
    font-size: 12px;
    margin-top: 4px;
}

.user-info small a:hover {
    color: #fff !important;
}

/* Scrollbar */
.sidebar::-webkit-scrollbar {
    width: 4px;
}

.sidebar::-webkit-scrollbar-track {
    background: transparent;
}

.sidebar::-webkit-scrollbar-thumb {
    background: rgba(255,255,255,0.2);
    border-radius: 10px;
}

/* Responsive - Mobile */
@media (max-width: 768px) {
    .sidebar {
        transform: translateX(-100%);
        width: 280px;
    }
    
    .sidebar.open {
        transform: translateX(0);
    }
    
    /* Hamburger menu button */
    .sidebar-toggle {
        display: flex !important;
    }
}

@media (min-width: 769px) {
    .sidebar-toggle {
        display: none !important;
    }
}

/* Animation for chevron */
.nav-section-header .fa-chevron-down {
    transition: transform 0.3s ease;
}

.nav-section-header .fa-chevron-down.rotated {
    transform: rotate(180deg);
}
</style>

<!-- Sidebar JavaScript -->
<script>
// Toggle section collapse/expand
function toggleSection(sectionId) {
    const section = document.getElementById(sectionId);
    const arrow = document.getElementById('admissionArrow');
    
    if (section.style.display === 'none') {
        section.style.display = 'block';
        if (arrow) arrow.classList.remove('rotated');
    } else {
        section.style.display = 'none';
        if (arrow) arrow.classList.add('rotated');
    }
}

// Mobile sidebar toggle
document.addEventListener('DOMContentLoaded', function() {
    // Create hamburger button if it doesn't exist
    if (!document.querySelector('.sidebar-toggle')) {
        const toggleBtn = document.createElement('button');
        toggleBtn.className = 'sidebar-toggle';
        toggleBtn.innerHTML = '<i class="fas fa-bars"></i>';
        toggleBtn.style.cssText = `
            position: fixed;
            top: 12px;
            left: 12px;
            z-index: 1001;
            background: #1a1a2e;
            color: #fff;
            border: none;
            border-radius: 8px;
            padding: 10px 14px;
            font-size: 18px;
            cursor: pointer;
            display: none;
            box-shadow: 0 2px 10px rgba(0,0,0,0.2);
        `;
        document.body.prepend(toggleBtn);
        
        toggleBtn.addEventListener('click', function() {
            const sidebar = document.querySelector('.sidebar');
            sidebar.classList.toggle('open');
        });
    }
    
    // Close sidebar when clicking outside on mobile
    document.addEventListener('click', function(e) {
        const sidebar = document.querySelector('.sidebar');
        const toggle = document.querySelector('.sidebar-toggle');
        if (window.innerWidth <= 768) {
            if (!sidebar.contains(e.target) && !toggle.contains(e.target)) {
                sidebar.classList.remove('open');
            }
        }
    });
});
</script>