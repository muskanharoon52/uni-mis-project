<!-- Sidebar Overlay -->
<div class="sidebar-overlay" id="sidebarOverlay"></div>

<!-- Sidebar -->
<div class="sidebar" id="sidebar">
    
    
    <div class="list-group list-group-flush">
        <!-- Dashboard -->
        <a href="../examination/dashboard.php" class="list-group-item list-group-item-action <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>">
            <i class="bi bi-speedometer2"></i> Dashboard
            <span class="badge bg-primary">Home</span>
        </a>
        
        <!-- Examination Section with Submenu -->
        <div class="list-group-item has-submenu" onclick="toggleSubmenu(this)">
            <i class="bi bi-calendar-event"></i> Examination
            <i class="bi bi-chevron-down float-end"></i>
        </div>
        <div class="submenu <?php echo (strpos($_SERVER['REQUEST_URI'], 'schedule') !== false || strpos($_SERVER['REQUEST_URI'], 'results') !== false || strpos($_SERVER['REQUEST_URI'], 'promote') !== false) ? 'open' : ''; ?>">
            <a href="../examination/schedule/index.php" class="list-group-item list-group-item-action <?php echo strpos($_SERVER['REQUEST_URI'], 'schedule') !== false ? 'active' : ''; ?>">
                <i class="bi bi-calendar-plus"></i> Schedule
                <span class="badge bg-info">CRUD</span>
            </a>
            <a href="../examination/results/index.php" class="list-group-item list-group-item-action <?php echo strpos($_SERVER['REQUEST_URI'], 'results') !== false ? 'active' : ''; ?>">
                <i class="bi bi-bar-chart"></i> Results
                <span class="badge bg-success">CRUD</span>
            </a>
            <a href="../examination/results/publish.php" class="list-group-item list-group-item-action">
                <i class="bi bi-cloud-upload"></i> Publish Results
                <span class="badge bg-warning">Publish</span>
            </a>
            <a href="../examination/promote/index.php" class="list-group-item list-group-item-action <?php echo strpos($_SERVER['REQUEST_URI'], 'promote') !== false ? 'active' : ''; ?>">
                <i class="bi bi-arrow-up-circle"></i> Promotion
                <span class="badge bg-danger">Promote</span>
            </a>
        </div>
        
        <!-- Reports -->
        <a href="#" class="list-group-item list-group-item-action">
            <i class="bi bi-clipboard-data"></i> Reports
            <span class="badge bg-secondary">New</span>
        </a>
        
        <!-- Export -->
        <a href="#" class="list-group-item list-group-item-action">
            <i class="bi bi-download"></i> Export Data
            <span class="badge bg-secondary">Excel</span>
        </a>
        
        
        
        
        
    </div>
</div>