<?php
define('SA_INIT', true);
require_once __DIR__ . '/includes/auth.php';
sa_guard();
$conn = getConnection();
$me = (int)($_SESSION['user_id'] ?? 0);

header('Content-Type: application/json');
$res = ['ok' => false, 'error' => 'Unknown action.'];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['ok' => false, 'error' => 'POST only.']);
    exit;
}

$action = $_POST['action'] ?? '';

switch ($action) {

    case 'set_permission':
        $moduleId = (int)($_POST['module_id'] ?? 0);
        $subId = $_POST['submodule_id'] !== '' && $_POST['submodule_id'] !== null ? (int)$_POST['submodule_id'] : null;
        $scope = $_POST['scope'] ?? '';
        $grantee = (int)($_POST['grantee'] ?? 0);
        $access = $_POST['access'] ?? '';
        if ($moduleId <= 0 || $grantee <= 0 || !in_array($scope, ['role', 'department', 'user'], true) || !in_array($access, ['allow', 'deny'], true)) {
            $res = ['ok' => false, 'error' => 'Invalid parameters.'];
            break;
        }
        if (sa_set_permission($moduleId, $subId, $scope, $grantee, $access, $me)) {
            $scopeName = ['role' => 'Role', 'department' => 'Department', 'user' => 'User'][$scope];
            $target = $subId ? "submodule #$subId" : "module #$moduleId";
            sa_log($access === 'deny' ? 'REVOKE_ACCESS' : 'GRANT_ACCESS', 'permission', $grantee, "$scopeName #$grantee -> $target ($access)");
            $res = ['ok' => true];
        } else {
            $res = ['ok' => false, 'error' => 'Database error: ' . mysqli_error($conn)];
        }
        break;

    case 'clear_permission':
        $moduleId = (int)($_POST['module_id'] ?? 0);
        $subId = $_POST['submodule_id'] !== '' && $_POST['submodule_id'] !== null ? (int)$_POST['submodule_id'] : null;
        $scope = $_POST['scope'] ?? '';
        $grantee = (int)($_POST['grantee'] ?? 0);
        if ($moduleId <= 0 || $grantee <= 0 || !in_array($scope, ['role', 'department', 'user'], true)) {
            $res = ['ok' => false, 'error' => 'Invalid parameters.'];
            break;
        }
        if (sa_clear_permission($moduleId, $subId, $scope, $grantee)) {
            $scopeName = ['role' => 'Role', 'department' => 'Department', 'user' => 'User'][$scope];
            $target = $subId ? "submodule #$subId" : "module #$moduleId";
            sa_log('RESET_ACCESS', 'permission', $grantee, "$scopeName #$grantee -> $target (rule cleared, back to default)");
            $res = ['ok' => true];
        } else {
            $res = ['ok' => false, 'error' => 'Database error: ' . mysqli_error($conn)];
        }
        break;

    case 'set_control':
        $userType = ($_POST['user_type'] ?? '') === 'sbe' ? 'sbe' : 'portal';
        $userId = (int)($_POST['user_id'] ?? 0);
        $status = $_POST['status'] ?? '';
        $reason = (string)($_POST['reason'] ?? '');
        if ($userId <= 0 || !in_array($status, ['Active', 'Blocked', 'Frozen'], true)) {
            $res = ['ok' => false, 'error' => 'Invalid parameters.'];
            break;
        }
        if (sa_apply_control($userType, $userId, $status, $reason !== '' ? $reason : null, $me)) {
            $verb = ['Blocked' => 'BLOCK_USER', 'Frozen' => 'FREEZE_USER', 'Active' => 'UNBLOCK_USER'][$status] ?? 'CONTROL_USER';
            sa_log($verb, $userType === 'sbe' ? 'sbe_auth' : 'user', $userId, "Account set to $status" . ($reason !== '' ? " | reason: $reason" : ''));
            $res = ['ok' => true, 'sessions_killed' => ($status === 'Blocked' || $status === 'Frozen')];
        } else {
            $res = ['ok' => false, 'error' => 'Database error: ' . mysqli_error($conn)];
        }
        break;

    case 'terminate_session':
        $sessId = (string)($_POST['session_id'] ?? '');
        if ($sessId === '') {
            $res = ['ok' => false, 'error' => 'Missing session id.'];
            break;
        }
        if (sa_terminate_session($sessId)) {
            sa_log('TERMINATE_SESSION', 'session', 0, "Terminated session $sessId");
            $res = ['ok' => true];
        } else {
            $res = ['ok' => false, 'error' => 'Session not found or already expired.'];
        }
        break;

    case 'terminate_all':
        $userType = ($_POST['user_type'] ?? '') === 'sbe' ? 'sbe' : 'portal';
        $userId = (int)($_POST['user_id'] ?? 0);
        if ($userId <= 0) {
            $res = ['ok' => false, 'error' => 'Invalid user.'];
            break;
        }
        $killed = sa_terminate_all_sessions($userType, $userId);
        sa_log('TERMINATE_SESSIONS', $userType === 'sbe' ? 'sbe_auth' : 'user', $userId, "Terminated $killed live session(s)");
        $res = ['ok' => true, 'killed' => $killed];
        break;

    default:
        $res = ['ok' => false, 'error' => 'Unknown action.'];
}

echo json_encode($res);
