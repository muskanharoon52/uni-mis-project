<?php
if (!defined('BASE_URL')) {
    define('BASE_URL', 'http://localhost/uni-mis-project/');
}

$currentPage = basename($_SERVER['PHP_SELF']);
$currentDir = basename(dirname($_SERVER['PHP_SELF']));
$exam_base = BASE_URL . 'examination/';
$userName = $_SESSION['full_name'] ?? 'User';
$userInitial = strtoupper(substr($userName, 0, 1));
?>
