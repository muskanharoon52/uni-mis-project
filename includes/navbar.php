<nav class="navbar navbar-expand-lg navbar-dark bg-primary navbar-fixed">
    <div class="container-fluid">
        <!-- Hamburger Menu Button for Sidebar -->
        <button class="hamburger-btn" id="sidebarToggle" aria-label="Toggle Sidebar">
            <div class="bar-container">
                <span class="bar"></span>
                <span class="bar"></span>
                <span class="bar"></span>
            </div>
        </button>
        
        <a class="navbar-brand" href="../index.php">
            <i class="bi bi-mortarboard"></i> University MIS
        </a>
        
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        
        
                    </a>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="../examination/schedule/index.php">Schedule</a></li>
                        <li><a class="dropdown-item" href="../examination/results/index.php">Results</a></li>
                        <li><a class="dropdown-item" href="../examination/promote/index.php">Promotion</a></li>
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