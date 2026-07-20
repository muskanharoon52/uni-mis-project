<nav class="navbar navbar-expand-lg navbar-dark bg-primary navbar-fixed">
    <div class="container-fluid">
        <?php if (empty($hideSidebarToggle)): ?>
            <!-- Hamburger Menu Button for Sidebar -->
            <button class="hamburger-btn" id="sidebarToggle" aria-label="Toggle Sidebar">
                <div class="bar-container">
                    <span class="bar"></span>
                    <span class="bar"></span>
                    <span class="bar"></span>
                </div>
            </button>
        <?php endif; ?>
        
        <a class="navbar-brand" href="/uni-mis-project/index.php">
            <i class="bi bi-mortarboard"></i> University MIS
        </a>
        
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto">
                <?php if (!empty($showDashboardBackButton)): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="/uni-mis-project/examination/dashboard.php">
                            <i class="bi bi-arrow-left"></i> Back to Dashboard
                        </a>
                    </li>
                <?php endif; ?>
                <li class="nav-item">
                    
                </li>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle <?php echo strpos($_SERVER['REQUEST_URI'], 'schedule') !== false || strpos($_SERVER['REQUEST_URI'], 'results') !== false || strpos($_SERVER['REQUEST_URI'], 'promote') !== false ? 'active' : ''; ?>" 
                       href="#" id="examDropdown" role="button" data-bs-toggle="dropdown">
                        <i class="bi bi-calendar-event"></i> Examination
                    </a>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="/uni-mis-project/examination/schedule/index.php">Schedule</a></li>
                        <li><a class="dropdown-item" href="/uni-mis-project/examination/results/index.php">Results</a></li>
                        <li><a class="dropdown-item" href="/uni-mis-project/examination/promote/index.php">Promotion</a></li>
                    </ul>
                </li>
            </ul>
            <ul class="navbar-nav">
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown">
                        <i class="bi bi-person-circle"></i> 
                        <?php 
                        if (isset($_SESSION['full_name'])) {
                            echo $_SESSION['full_name'];
                        } else {
                            echo 'Admin';
                        }
                        ?>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item" href="#"><i class="bi bi-person"></i> Profile</a></li>
                        <li><a class="dropdown-item" href="#"><i class="bi bi-gear"></i> Settings</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item text-danger" href="../logout.php"><i class="bi bi-box-arrow-right"></i> Logout</a></li>
                    </ul>
                </li>
            </ul>
        </div>
    </div>
</nav>
