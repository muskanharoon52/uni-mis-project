<?php
/**
 * Super Admin Control & RBAC System - shared helpers & guards.
 * Self-contained: does NOT modify any other module.
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../../config/db_connect.php';

if (!function_exists('sa_current_user')) {
    function sa_current_user() {
        $conn = getConnection();
        $uid = (int)($_SESSION['user_id'] ?? 0);
        if ($uid <= 0) return null;
        $res = mysqli_query($conn, "SELECT u.*, r.role_name FROM users u LEFT JOIN roles r ON u.role_id = r.role_id WHERE u.user_id = $uid LIMIT 1");
        $row = $res ? mysqli_fetch_assoc($res) : null;
        if (!$row) return null;
        $row['_type'] = 'portal';
        return $row;
    }
}

function sa_is_super_admin($user = null) {
    if ($user === null) $user = sa_current_user();
    if (!$user) return false;
    return strtolower($user['role_name'] ?? '') === 'super admin';
}

function sa_guard() {
    if (!sa_is_super_admin()) {
        header('Location: /uni-mis-project/');
        exit;
    }
}

function sa_client_ip() {
    return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
}

/**
 * Permanently log a sensitive super-admin action.
 * Written to sa_audit_logs (dedicated) + activity_logs (system-wide view).
 */
function sa_log($action, $target_type, $target_id = null, $details = null) {
    $conn = getConnection();
    $admin = (int)($_SESSION['user_id'] ?? 0);
    $ip = sa_client_ip();
    $details = $details !== null ? (string)$details : null;

    $stmt = mysqli_prepare($conn, "INSERT INTO sa_audit_logs (admin_user_id, action, target_type, target_id, details, ip_address) VALUES (?, ?, ?, ?, ?, ?)");
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, 'ississ', $admin, $action, $target_type, $target_id, $details, $ip);
        @mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    }

    require_once __DIR__ . '/../../includes/activity.php';
    $safe = is_string($details) ? substr($details, 0, 900) : $details;
    log_activity('Super Admin', $action, $target_type, $target_id, $safe);
}

/* ============================================================
 * LIVE SESSION MONITORING (via PHP session files - no module touched)
 * ============================================================ */
function sa_session_dir() {
    $p = session_save_path();
    if (empty($p)) $p = sys_get_temp_dir();
    return $p;
}

/** Extract a top-level serialized session value (int or string). */
function sa_ser_get($raw, $key) {
    $key = preg_quote($key, '/');
    if (preg_match('/' . $key . '\|i:(-?\d+);/', $raw, $m)) return $m[1];
    if (preg_match('/' . $key . '\|s:\d+:"((?:[^"\\\\]|\\\\.)*)"/', $raw, $m)) return $m[1];
    return null;
}

/** Extract a value from a nested serialized array (e.g. auth_user). */
function sa_ser_arr_get($raw, $key) {
    $key = preg_quote($key, '/');
    if (preg_match('/s:\d+:"' . $key . '";i:(-?\d+);/', $raw, $m)) return $m[1];
    if (preg_match('/s:\d+:"' . $key . '";s:\d+:"((?:[^"\\\\]|\\\\.)*)"/', $raw, $m)) return $m[1];
    return null;
}

function sa_scan_sessions() {
    $dir = sa_session_dir();
    $out = [];
    if (!is_dir($dir)) return $out;
    $files = @scandir($dir);
    if (!$files) return $out;
    foreach ($files as $f) {
        if (strpos($f, 'sess_') !== 0) continue;
        $path = $dir . DIRECTORY_SEPARATOR . $f;
        $raw = @file_get_contents($path);
        if ($raw === false || $raw === '') continue;
        $sessId = substr($f, 5);

        $uId = sa_ser_get($raw, 'user_id');
        if ($uId !== null && $uId !== '') {
            $out[] = [
                'sess_id' => $sessId, 'user_type' => 'portal',
                'user_id' => (int)$uId,
                'role_name' => (string)sa_ser_get($raw, 'role_name'),
                'full_name' => (string)sa_ser_get($raw, 'full_name'),
                'username' => (string)sa_ser_get($raw, 'username'),
                'sbe_auth_id' => 0, 'sbe_role' => '', 'sbe_login_id' => '',
                'mtime' => @filemtime($path),
            ];
        }

        if (strpos($raw, 'auth_user|a:') !== false) {
            $authId = sa_ser_arr_get($raw, 'auth_id');
            if ($authId !== null) {
                $out[] = [
                    'sess_id' => $sessId, 'user_type' => 'sbe',
                    'user_id' => 0, 'role_name' => '', 'full_name' => (string)sa_ser_arr_get($raw, 'display_name'),
                    'username' => (string)sa_ser_arr_get($raw, 'login_id'),
                    'sbe_auth_id' => (int)$authId,
                    'sbe_role' => (string)sa_ser_arr_get($raw, 'role'),
                    'sbe_login_id' => (string)sa_ser_arr_get($raw, 'login_id'),
                    'mtime' => @filemtime($path),
                ];
            }
        }
    }
    return $out;
}

function sa_terminate_session($sessId) {
    $sessId = preg_replace('/[^A-Za-z0-9,-]/', '', (string)$sessId);
    if ($sessId === '') return false;
    $path = sa_session_dir() . DIRECTORY_SEPARATOR . 'sess_' . $sessId;
    if (is_file($path)) { @unlink($path); return true; }
    return false;
}

function sa_terminate_all_sessions($userType, $userId) {
    $killed = 0;
    foreach (sa_scan_sessions() as $s) {
        if ($userType === 'sbe' && $s['user_type'] === 'sbe' && (int)$s['sbe_auth_id'] === (int)$userId) {
            if (sa_terminate_session($s['sess_id'])) $killed++;
        }
        if ($userType === 'portal' && $s['user_type'] === 'portal' && (int)$s['user_id'] === (int)$userId) {
            if (sa_terminate_session($s['sess_id'])) $killed++;
        }
    }
    return $killed;
}

/* ============================================================
 * USER CONTROLS (block / freeze / unblock)
 * ============================================================ */
function sa_control_status($userType, $userId) {
    $conn = getConnection();
    $userType = $userType === 'sbe' ? 'sbe' : 'portal';
    $userId = (int)$userId;
    $stmt = mysqli_prepare($conn, "SELECT status, reason FROM sa_user_controls WHERE user_type = ? AND user_id = ? LIMIT 1");
    if (!$stmt) return ['status' => 'Active', 'reason' => null];
    mysqli_stmt_bind_param($stmt, 'si', $userType, $userId);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    $row = $res->fetch_assoc();
    mysqli_stmt_close($stmt);
    return $row ? ['status' => $row['status'], 'reason' => $row['reason']] : ['status' => 'Active', 'reason' => null];
}

/**
 * Apply block/freeze/unblock. Blocking/freezing also kills live sessions.
 */
function sa_apply_control($userType, $userId, $status, $reason = null, $adminId = 0) {
    $conn = getConnection();
    $userType = $userType === 'sbe' ? 'sbe' : 'portal';
    $userId = (int)$userId;
    $status = in_array($status, ['Active', 'Blocked', 'Frozen'], true) ? $status : 'Active';
    $reason = $reason !== null ? substr((string)$reason, 0, 255) : null;

    $stmt = mysqli_prepare($conn, "INSERT INTO sa_user_controls (user_type, user_id, status, reason, updated_by) VALUES (?, ?, ?, ?, ?)
                                   ON DUPLICATE KEY UPDATE status = VALUES(status), reason = VALUES(reason), updated_by = VALUES(updated_by)");
    mysqli_stmt_bind_param($stmt, 'sissi', $userType, $userId, $status, $reason, $adminId);
    $ok = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    if ($status === 'Blocked' || $status === 'Frozen') {
        sa_terminate_all_sessions($userType, $userId);
    }
    return $ok;
}

/* ============================================================
 * PERMISSION ENGINE
 * Resolution order (most specific wins):
 *   user > department > role ; submodule-level > module-level ; default = allow.
 * Super Admin always overrides (unrestricted).
 * ============================================================ */
function sa_permission_for($moduleId, $submoduleId, $granteeType, $granteeId) {
    $conn = getConnection();
    $moduleId = (int)$moduleId;
    $subId = $submoduleId !== null ? (int)$submoduleId : null;
    $granteeType = in_array($granteeType, ['role', 'department', 'user'], true) ? $granteeType : 'role';
    $granteeId = (int)$granteeId;

    $q = "SELECT access FROM sa_permissions WHERE module_id = $moduleId AND grantee_type = '$granteeType' AND grantee_id = $granteeId";
    if ($subId !== null) {
        $q .= " AND submodule_id = $subId";
    } else {
        $q .= " AND submodule_id IS NULL";
    }
    $q .= " ORDER BY created_at DESC LIMIT 1";
    $res = mysqli_query($conn, $q);
    $row = $res ? mysqli_fetch_assoc($res) : null;
    return $row ? $row['access'] : null;
}

function sa_find_rule($moduleId, $submoduleId, $granteeType, $granteeId) {
    $v = sa_permission_for($moduleId, $submoduleId, $granteeType, $granteeId);
    if ($v !== null) return $v;
    return null;
}

function sa_set_permission($moduleId, $submoduleId, $granteeType, $granteeId, $access, $adminId = 0) {
    $conn = getConnection();
    $moduleId = (int)$moduleId;
    $subId = $submoduleId !== null ? (int)$submoduleId : null;
    $granteeType = in_array($granteeType, ['role', 'department', 'user'], true) ? $granteeType : 'role';
    $granteeId = (int)$granteeId;
    $access = $access === 'deny' ? 'deny' : 'allow';

    // remove opposite-rule for the same scope to keep a single source of truth
    $opp = $access === 'allow' ? 'deny' : 'allow';
    if ($subId !== null) {
        $stmt = mysqli_prepare($conn, "DELETE FROM sa_permissions WHERE module_id=? AND submodule_id=? AND grantee_type=? AND grantee_id=? AND access=?");
        mysqli_stmt_bind_param($stmt, 'iisis', $moduleId, $subId, $granteeType, $granteeId, $opp);
    } else {
        $stmt = mysqli_prepare($conn, "DELETE FROM sa_permissions WHERE module_id=? AND submodule_id IS NULL AND grantee_type=? AND grantee_id=? AND access=?");
        mysqli_stmt_bind_param($stmt, 'isis', $moduleId, $granteeType, $granteeId, $opp);
    }
    @mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    $stmt = mysqli_prepare($conn, "INSERT INTO sa_permissions (module_id, submodule_id, grantee_type, grantee_id, access, created_by) VALUES (?, ?, ?, ?, ?, ?)");
    mysqli_stmt_bind_param($stmt, 'iisisi', $moduleId, $subId, $granteeType, $granteeId, $access, $adminId);
    $ok = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    return $ok;
}

function sa_clear_permission($moduleId, $submoduleId, $granteeType, $granteeId) {
    $conn = getConnection();
    $moduleId = (int)$moduleId;
    $subId = $submoduleId !== null ? (int)$submoduleId : null;
    $granteeType = in_array($granteeType, ['role', 'department', 'user'], true) ? $granteeType : 'role';
    $granteeId = (int)$granteeId;
    if ($subId !== null) {
        $stmt = mysqli_prepare($conn, "DELETE FROM sa_permissions WHERE module_id=? AND submodule_id=? AND grantee_type=? AND grantee_id=?");
        mysqli_stmt_bind_param($stmt, 'iisi', $moduleId, $subId, $granteeType, $granteeId);
    } else {
        $stmt = mysqli_prepare($conn, "DELETE FROM sa_permissions WHERE module_id=? AND submodule_id IS NULL AND grantee_type=? AND grantee_id=?");
        mysqli_stmt_bind_param($stmt, 'isi', $moduleId, $granteeType, $granteeId);
    }
    $ok = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    return $ok;
}

/**
 * Resolve effective access for a portal user to module / submodule.
 */
function sa_has_access($moduleId, $submoduleId = null, $user = null) {
    $conn = getConnection();
    if ($user === null) $user = sa_current_user();
    if (!$user || (int)$moduleId <= 0) return true;
    if (strtolower($user['role_name'] ?? '') === 'super admin') return true; // override

    $uid = (int)$user['user_id'];
    $roleId = (int)($user['role_id'] ?? 0);
    $deptId = sa_user_department_id($user, $conn);

    $scope = [];
    if ($deptId) $scope[] = ['p' => 2, 't' => 'department', 'id' => $deptId];
    if ($roleId) $scope[] = ['p' => 1, 't' => 'role', 'id' => $roleId];
    $scope[] = ['p' => 3, 't' => 'user', 'id' => $uid];
    usort($scope, fn($a, $b) => $b['p'] - $a['p']);

    foreach ($scope as $s) {
        $r = sa_find_rule($moduleId, $submoduleId, $s['t'], $s['id']);
        if ($r !== null) return $r === 'allow';
        $r = sa_find_rule($moduleId, null, $s['t'], $s['id']);
        if ($r !== null) return $r === 'allow';
    }
    return true; // default: allowed
}

function sa_user_department_id($user, $conn) {
    if (!$user) return 0;
    if (!empty($user['department_id'])) return (int)$user['department_id'];
    $uid = (int)($user['user_id'] ?? 0);
    $role = strtolower($user['role_name'] ?? '');
    if ($uid > 0 && $role === 'student') {
        $q = mysqli_query($conn, "SELECT p.department_id FROM students s JOIN programs p ON s.program_id = p.program_id WHERE s.user_id = $uid LIMIT 1");
        if ($q && $r = mysqli_fetch_assoc($q)) return (int)$r['department_id'];
    } elseif ($uid > 0 && $role === 'teacher') {
        $q = mysqli_query($conn, "SELECT department_id FROM teachers WHERE user_id = $uid LIMIT 1");
        if ($q && $r = mysqli_fetch_assoc($q)) return (int)$r['department_id'];
    }
    return 0;
}

function sa_es($v) {
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}

function sa_ago($ts) {
    if (!$ts) return '—';
    $d = time() - (int)$ts;
    if ($d < 60) return 'just now';
    if ($d < 3600) return floor($d / 60) . 'm ago';
    if ($d < 86400) return floor($d / 3600) . 'h ago';
    return floor($d / 86400) . 'd ago';
}
