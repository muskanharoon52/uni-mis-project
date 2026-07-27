<?php
if (!defined('BASE_URL')) {
    define('BASE_URL', 'http://localhost/uni-mis-project/');
}
$exam_base = BASE_URL . 'examination/';
$currentPage = basename($_SERVER['PHP_SELF']);
$currentDir = basename(dirname($_SERVER['PHP_SELF']));
?>

<div class="sidebar">
    <div class="brand">
        <i class="fas fa-university fa-2x mb-2"></i>
        <h4>University MIS</h4>
        <small>Examination Module</small>
    </div>

    <div class="text-center text-white-50 mb-3" style="padding: 10px;">
        <i class="fas fa-user-circle fa-3x"></i>
        <div class="fw-bold mt-1"><?php echo $_SESSION['full_name'] ?? 'User'; ?></div>
        <small><?php echo ucfirst($_SESSION['role_name'] ?? 'sso'); ?></small>
    </div>

    <nav class="nav flex-column">
        <a href="<?php echo $exam_base; ?>dashboard.php"
           class="nav-link <?php echo ($currentPage == 'dashboard.php') ? 'active' : ''; ?>">
            <i class="fas fa-home"></i> Dashboard
        </a>
        <a href="<?php echo $exam_base; ?>schedule/index.php"
           class="nav-link <?php echo ($currentDir == 'schedule' && $currentPage == 'index.php') ? 'active' : ''; ?>">
            <i class="fas fa-calendar"></i> Exam Schedule
        </a>
        <a href="<?php echo $exam_base; ?>schedule/add.php"
           class="nav-link <?php echo ($currentDir == 'schedule' && $currentPage == 'add.php') ? 'active' : ''; ?>">
            <i class="fas fa-plus-circle"></i> Add Schedule
        </a>
        <a href="<?php echo $exam_base; ?>results/index.php"
           class="nav-link <?php echo ($currentDir == 'results' && $currentPage == 'index.php') ? 'active' : ''; ?>">
            <i class="fas fa-chart-bar"></i> Results
        </a>
        <a href="<?php echo $exam_base; ?>results/add.php"
           class="nav-link <?php echo ($currentDir == 'results' && $currentPage == 'add.php') ? 'active' : ''; ?>">
            <i class="fas fa-plus-circle"></i> Add Result
        </a>
        <a href="<?php echo $exam_base; ?>results/publish.php"
           class="nav-link <?php echo ($currentDir == 'results' && $currentPage == 'publish.php') ? 'active' : ''; ?>">
            <i class="fas fa-cloud-upload"></i> Publish Results
        </a>
        <a href="<?php echo $exam_base; ?>promote/promote.php"
           class="nav-link <?php echo ($currentDir == 'promote' && $currentPage == 'promote.php') ? 'active' : ''; ?>">
            <i class="fas fa-arrow-up"></i> Promote Students
        </a>

        <hr style="border-color: rgba(255,255,255,0.1);">

        <a href="<?php echo BASE_URL; ?>modules/sso/logout.php" class="nav-link text-danger">
            <i class="fas fa-sign-out-alt"></i> Logout
        </a>
    </nav>
</div>

<style>
.sidebar {
    width: 250px;
    height: 100vh;
    background: linear-gradient(180deg, #1a1a2e 0%, #16213e 100%);
    color: white;
    position: fixed;
    left: 0;
    top: 0;
    overflow-y: auto;
    padding-bottom: 20px;
    z-index: 1000;
    transition: all 0.3s;
}

.sidebar::-webkit-scrollbar { width: 4px; }
.sidebar::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.2); border-radius: 10px; }

.sidebar .brand {
    padding: 25px 20px;
    text-align: center;
    border-bottom: 1px solid rgba(255,255,255,0.08);
}

.sidebar .brand h4 {
    font-weight: 700;
    margin: 0;
    letter-spacing: 1px;
}

.sidebar .brand small {
    color: rgba(255,255,255,0.5);
    font-size: 12px;
    letter-spacing: 2px;
}

.sidebar .nav-link {
    color: rgba(255,255,255,0.6);
    padding: 12px 22px;
    border-radius: 0;
    transition: all 0.3s;
    font-size: 14px;
    border-left: 3px solid transparent;
    text-decoration: none;
    display: block;
}

.sidebar .nav-link:hover {
    color: white;
    background: rgba(255,255,255,0.05);
}

.sidebar .nav-link.active {
    color: white;
    background: rgba(102, 126, 234, 0.2);
    border-left-color: #667eea;
}

.sidebar .nav-link i {
    width: 22px;
    margin-right: 12px;
    text-align: center;
    font-size: 15px;
}

.sidebar .nav-link.text-danger:hover {
    background: rgba(220, 53, 69, 0.15);
}

.sidebar hr {
    border-color: rgba(255,255,255,0.1);
    margin: 10px 20px;
}

@media (max-width: 768px) {
    .sidebar {
        width: 100%;
        height: auto;
        position: relative;
        padding-bottom: 10px;
    }
}
</style>
