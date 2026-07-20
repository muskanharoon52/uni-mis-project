<?php
// ============================================
// NAVBAR - Without Admin User Section
// ============================================
?>

<!-- Top Navbar -->
<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container-fluid">
        <!-- Toggle Button for Sidebar -->
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        
        <!-- Brand -->
        <a class="navbar-brand" href="Dashboard.php">
            <i class="fas fa-university"></i> University MIS
        </a>
        
        <!-- Navbar Links -->
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto">
                <!-- Notification Bell -->
                <li class="nav-item">
                    <a class="nav-link" href="#" title="Notifications">
                        <i class="fas fa-bell"></i>
                        <span class="badge bg-danger">3</span>
                    </a>
                </li>
                
                <!-- Logout Button Only -->
                <li class="nav-item">
                    <a class="nav-link text-danger" href="logout.php">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </a>
                </li>
            </ul>
        </div>
    </div>
</nav>

<style>
    .navbar {
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }
    
    .navbar-brand {
        font-weight: 600;
        font-size: 20px;
    }
    
    .navbar-brand i {
        color: #3498db;
        margin-right: 8px;
    }
    
    .nav-link .badge {
        font-size: 10px;
        padding: 2px 6px;
        position: relative;
        top: -8px;
        left: -5px;
    }
    
    .nav-link.text-danger:hover {
        color: #ff6b6b !important;
    }
</style>