<?php
/**
 * Shared helper: renders a main module's granted submodules (from the Super Admin
 * sa_submodules registry) into that module's sidebar.
 *
 * - Only submodules with status 'Active' are shown.
 * - Submodules already hardcoded in the module's own sidebar are skipped
 *   via $excludeKeys (submodule_key) and $excludeRoutes (route).
 * - Submodules whose route does not resolve to a real page are skipped so the
 *   sidebar never shows a dead link.
 *
 * Each main-module header calls: sa_render_submodules('finance', [...excluded keys...]);
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
// config/db_connect.php declares a global $user ("root"); preserve any existing
// $user (e.g. the module's logged-in user array) across the include.
$__saHasUser = array_key_exists('user', $GLOBALS);
$__saSavedUser = $GLOBALS['user'] ?? null;
require_once __DIR__ . '/../config/db_connect.php';
if ($__saHasUser) {
    $GLOBALS['user'] = $__saSavedUser;
} else {
    unset($GLOBALS['user']);
}

function sa_route_exists($route) {
    $p = parse_url((string)$route, PHP_URL_PATH);
    if (!$p) return false;
    $base = rtrim($_SERVER['DOCUMENT_ROOT'] ?? '', '/\\');
    if ($base === '') return true;
    $fs = $base . '/' . ltrim($p, '/');
    if (is_file($fs)) return true;
    if (is_dir($fs)) return is_file(rtrim($fs, '/\\') . '/index.php');
    return false;
}

function sa_render_submodules($moduleKey, array $excludeKeys = [], array $excludeRoutes = []) {
    $conn = getConnection();
    $moduleKey = mysqli_real_escape_string($conn, (string)$moduleKey);
    if ($moduleKey === '') return;

    $res = mysqli_query($conn, "SELECT s.submodule_key, s.submodule_name, s.route
        FROM sa_submodules s
        JOIN sa_modules m ON m.module_id = s.module_id
        WHERE m.module_key = '$moduleKey' AND s.status = 'Active'
        ORDER BY s.sort_order, s.submodule_id");
    if (!$res) return;

    $rows = [];
    while ($r = mysqli_fetch_assoc($res)) {
        if ($r['submodule_key'] === 'dashboard') continue;
        if (in_array($r['submodule_key'], $excludeKeys, true)) continue;
        $route = trim($r['route'] ?? '');
        if ($route === '') continue;
        if (in_array($route, $excludeRoutes, true)) continue;
        if (!sa_route_exists($route)) continue;
        $rows[] = ['name' => $r['submodule_name'], 'route' => $route];
    }
    if (!$rows) return;

    $currentUri = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?? '';

    echo '<span class="nav-section-label">Sub Modules</span>';
    foreach ($rows as $r) {
        $target = parse_url($r['route'], PHP_URL_PATH) ?? $r['route'];
        $active = ($currentUri !== '' && $target !== '' && strpos($currentUri, $target) !== false) ? ' active' : '';
        echo '<a class="' . $active . '" href="' . htmlspecialchars($r['route']) . '">' . htmlspecialchars($r['name']) . '</a>';
    }
}
