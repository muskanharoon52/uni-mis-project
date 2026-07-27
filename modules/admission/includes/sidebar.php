<div class="sidebar">
    <div class="brand">
        <h4><i class="fas fa-graduation-cap"></i> Admission</h4>
        <small>Management System</small>
    </div>
    <nav>
        <a class="nav-link <?= basename(dirname($_SERVER['PHP_SELF'])) == 'dashboard' ? 'active' : '' ?>" 
           href="<?= BASE_URL ?>dashboard/">
            <i class="fas fa-home"></i> Dashboard
        </a>
        <a class="nav-link <?= basename(dirname($_SERVER['PHP_SELF'])) == 'applications' ? 'active' : '' ?>" 
           href="<?= BASE_URL ?>applications/">
            <i class="fas fa-file-alt"></i> Applications
        </a>
        <a class="nav-link <?= basename(dirname($_SERVER['PHP_SELF'])) == 'students' ? 'active' : '' ?>" 
           href="<?= BASE_URL ?>students/">
            <i class="fas fa-users"></i> Students
        </a>
        <a class="nav-link <?= basename(dirname($_SERVER['PHP_SELF'])) == 'fees' ? 'active' : '' ?>" 
           href="<?= BASE_URL ?>fees/">
            <i class="fas fa-money-bill"></i> Fees
        </a>
        <a class="nav-link <?= basename(dirname($_SERVER['PHP_SELF'])) == 'scholarships' ? 'active' : '' ?>" 
           href="<?= BASE_URL ?>scholarships/">
            <i class="fas fa-trophy"></i> Scholarships
        </a>
        <a class="nav-link <?= basename(dirname($_SERVER['PHP_SELF'])) == 'reports' ? 'active' : '' ?>" 
           href="<?= BASE_URL ?>reports/">
            <i class="fas fa-chart-bar"></i> Reports
        </a>
    </nav>
    <div class="user-info">
        <i class="fas fa-user-circle"></i> <?= $_SESSION['full_name'] ?? $_SESSION['user_name'] ?? 'User' ?>
        <br><small><i class="fas fa-sign-out-alt"></i> <a href="/uni-mis-project/logout.php">Logout</a></small>
    </div>
</div>
