<?php
// 1. Authentication/logout.php
// User logout

require_once '../../15. Includes/auth.php';

logoutUser();
header("Location: login.php");
exit;