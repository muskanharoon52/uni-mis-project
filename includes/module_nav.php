<?php
// includes/module_nav.php
// Dynamic, grant-aware module navigation for the main portal sidebars.
//
// It uses the Super Admin module registry (sa_modules / sa_submodules) and the
// permission engine (sa_permissions) to decide which submodules are visible to
// the current portal user in the side panel of a main module:
//   - Super Admin always sees every submodule (root override).
//   - A department that has explicit access rules for a module only sees the
//     submodules that were granted to it (checked by the Super Admin).
//   - Everyone else (no explicit rules) keeps the default-allow behaviour and
//     sees the full active registry for that module.

if (!defined('MODULE_NAV_LOADED')) {
    define('MODULE_NAV_LOADED', true);

    function mn_conn()
    {
        static $conn = null;
        if ($conn === null) {
            $conn = getConnection();
        }
        return $conn;
    }

    function mn_current_user(): ?array
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $uid = (int) ($_SESSION['user_id'] ?? 0);
        if ($uid <= 0) {
            return null;
        }
        $res = mysqli_query(mn_conn(), "SELECT u.*, r.role_name FROM users u LEFT JOIN roles r ON r.role_id = u.role_id WHERE u.user_id = $uid LIMIT 1");
        $row = $res ? mysqli_fetch_assoc($res) : null;
        return $row ?: null;
    }

    function mn_is_super_admin(?array $user): bool
    {
        return strtolower((string) ($user['role_name'] ?? '')) === 'super admin';
    }

    function mn_user_department_id(?array $user): int
    {
        if (!$user) {
            return 0;
        }
        if (!empty($user['department_id'])) {
            return (int) $user['department_id'];
        }
        $uid = (int) ($user['user_id'] ?? 0);
        $role = strtolower((string) ($user['role_name'] ?? ''));
        if ($uid > 0 && $role === 'student') {
            $res = mysqli_query(mn_conn(), "SELECT p.department_id FROM students s JOIN programs p ON p.program_id = s.program_id WHERE s.user_id = $uid LIMIT 1");
            if ($res && $r = mysqli_fetch_assoc($res)) {
                return (int) $r['department_id'];
            }
        } elseif ($uid > 0 && $role === 'teacher') {
            $res = mysqli_query(mn_conn(), "SELECT department_id FROM teachers WHERE user_id = $uid LIMIT 1");
            if ($res && $r = mysqli_fetch_assoc($res)) {
                return (int) $r['department_id'];
            }
        }
        return 0;
    }

    function mn_find_rule(int $moduleId, ?int $submoduleId, string $granteeType, int $granteeId): ?string
    {
        $moduleId = max(1, $moduleId);
        $granteeId = max(1, $granteeId);
        $granteeType = in_array($granteeType, ['role', 'department', 'user'], true) ? $granteeType : 'role';
        $q = "SELECT access FROM sa_permissions WHERE module_id = $moduleId";
        if ($submoduleId !== null) {
            $q .= " AND submodule_id = " . max(1, $submoduleId);
        } else {
            $q .= " AND submodule_id IS NULL";
        }
        $q .= " AND grantee_type = '$granteeType' AND grantee_id = $granteeId ORDER BY created_at DESC LIMIT 1";
        $res = mysqli_query(mn_conn(), $q);
        $row = $res ? mysqli_fetch_assoc($res) : null;
        return $row ? (string) $row['access'] : null;
    }

    function mn_dept_has_rules(int $moduleId, int $deptId): bool
    {
        if ($deptId <= 0) {
            return false;
        }
        $res = mysqli_query(mn_conn(), "SELECT COUNT(*) c FROM sa_permissions WHERE module_id = " . max(1, $moduleId) . " AND grantee_type = 'department' AND grantee_id = $deptId");
        $row = $res ? mysqli_fetch_assoc($res) : null;
        return ((int) ($row['c'] ?? 0)) > 0;
    }

    /**
     * Is a submodule visible to the given portal user under a main module?
     * Resolution order (most specific wins): user > department > role.
     * A department that is explicitly configured for the module only sees the
     * submodules it was granted (allow rules).
     */
    function mn_submodule_visible(int $moduleId, int $submoduleId, ?array $user): bool
    {
        if (mn_is_super_admin($user)) {
            return true;
        }
        $uid = (int) ($user['user_id'] ?? 0);
        $roleId = (int) ($user['role_id'] ?? 0);
        $deptId = mn_user_department_id($user);

        $scopes = [];
        if ($uid > 0) {
            $scopes[] = ['type' => 'user', 'id' => $uid];
        }
        if ($deptId > 0) {
            $scopes[] = ['type' => 'department', 'id' => $deptId];
        }
        if ($roleId > 0) {
            $scopes[] = ['type' => 'role', 'id' => $roleId];
        }

        foreach ($scopes as $scope) {
            $subRule = mn_find_rule($moduleId, $submoduleId, $scope['type'], $scope['id']);
            if ($subRule !== null) {
                return $subRule === 'allow';
            }
            $modRule = mn_find_rule($moduleId, null, $scope['type'], $scope['id']);
            if ($modRule !== null) {
                return $modRule === 'allow';
            }
            if ($scope['type'] === 'department' && mn_dept_has_rules($moduleId, $scope['id'])) {
                return false;
            }
        }
        return true; // default: allowed
    }

    function mn_active_submodules(int $moduleId, array $excludeKeys = []): array
    {
        $exclude = [];
        foreach ($excludeKeys as $key) {
            $exclude[] = "'" . strtolower((string) $key) . "'";
        }
        $excludeSql = $exclude ? ' AND submodule_key NOT IN (' . implode(',', $exclude) . ')' : '';
        $out = [];
        $res = mysqli_query(mn_conn(), "SELECT * FROM sa_submodules WHERE module_id = " . max(1, $moduleId) . " AND status = 'Active'" . $excludeSql . " ORDER BY sort_order, submodule_id");
        if ($res) {
            while ($row = mysqli_fetch_assoc($res)) {
                $out[] = $row;
            }
        }
        return $out;
    }

    function mn_visible_submodules(int $moduleId, ?array $user = null, array $excludeKeys = []): array
    {
        if ($user === null) {
            $user = mn_current_user();
        }
        $out = [];
        foreach (mn_active_submodules($moduleId, $excludeKeys) as $sub) {
            if (mn_submodule_visible($moduleId, (int) $sub['submodule_id'], $user)) {
                $out[] = $sub;
            }
        }
        return $out;
    }

    function mn_is_active(string $route, ?string $currentPage = null, ?string $currentFolder = null): bool
    {
        if ($route === '') {
            return false;
        }
        if ($currentPage === null) {
            $currentPage = basename($_SERVER['PHP_SELF'] ?? '');
        }
        if ($currentFolder === null) {
            $currentFolder = basename(dirname($_SERVER['PHP_SELF'] ?? ''));
        }
        $route = trim($route, '/');
        $segments = explode('/', $route);
        $last = (string) end($segments);
        $cPage = strtolower($currentPage);
        $cFolder = strtolower($currentFolder);
        if (strpos($last, '.php') === false) {
            return $cFolder === strtolower($last);
        }
        if (strtolower($last) === $cPage) {
            return true;
        }
        if (count($segments) >= 2) {
            $parent = $segments[count($segments) - 2];
            return $cFolder === strtolower($parent);
        }
        return false;
    }

    function mn_link(string $route): string
    {
        if ($route === '') {
            return '#';
        }
        if (preg_match('#^https?://#i', $route)) {
            return $route;
        }
        if (strpos($route, '/') === 0) {
            return $route;
        }
        return (defined('BASE_URL') ? BASE_URL : '/uni-mis-project/') . ltrim($route, '/');
    }

    /**
     * Render the granted submodules of a main module as sidebar links.
     * Uses the same markup as the portal sidebars (.nav-section-label + links).
     */
    function mn_render_nav(int $moduleId, array $excludeKeys = [], ?array $user = null, ?string $label = null): void
    {
        $user = $user ?? mn_current_user();
        $items = mn_visible_submodules($moduleId, $user, $excludeKeys);
        if (!$items) {
            return;
        }
        if ($label !== null) {
            echo '<span class="nav-section-label">' . htmlspecialchars($label) . '</span>';
        }
        foreach ($items as $sub) {
            $active = !empty($sub['route']) && mn_is_active($sub['route']) ? 'active' : '';
            echo '<a class="' . $active . '" href="' . htmlspecialchars(mn_link((string) $sub['route'])) . '">'
                . htmlspecialchars((string) $sub['submodule_name']) . '</a>';
        }
    }
}
