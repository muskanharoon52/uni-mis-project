<?php
if (!defined('SA_INIT')) {
    require_once __DIR__ . '/auth.php';
    sa_guard();
}
$sa_user = sa_current_user();
$sa_online = count(sa_scan_sessions());
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= sa_es($sa_page_title ?? 'Super Admin Control') ?> | Super Admin</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
<link rel="stylesheet" href="<?= BASE_URL ?>modules/lms/public/assets/style.css?v=<?= filemtime($_SERVER['DOCUMENT_ROOT'] . '/uni-mis-project/modules/lms/public/assets/style.css') ?>">
<style>
    /* ===== Super Admin components re-themed onto shared light tokens ===== */
    .page-title{font-size:1.35rem;font-weight:800;letter-spacing:-.02em;color:var(--text-strong);margin-bottom:4px}
    .page-sub{color:var(--text-secondary);font-size:.84rem;margin-bottom:22px}
    .card h4{font-size:.95rem;font-weight:700;color:var(--text-strong);margin-bottom:14px;display:flex;align-items:center;gap:8px}
    .grid{display:grid;gap:16px;margin-bottom:18px}
    .g4{grid-template-columns:repeat(4,1fr)}
    .g3{grid-template-columns:repeat(3,1fr)}
    .g2{grid-template-columns:repeat(2,1fr)}
    .kpi{background:var(--panel);border:1px solid var(--border);border-radius:var(--radius);padding:18px}
    .kpi .k-top{display:flex;align-items:center;justify-content:space-between}
    .kpi .k-ic{width:38px;height:38px;border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:1.05rem;flex-shrink:0}
    .kpi .k-val{font-size:1.7rem;font-weight:800;margin-top:10px;letter-spacing:-.02em;color:var(--text-strong)}
    .kpi .k-lbl{color:var(--text-secondary);font-size:.78rem;font-weight:600;margin-top:2px}
    .btn-warn{background:var(--warning-bg);border-color:var(--warning-border);color:var(--warning)}
    .btn-warn:hover{background:var(--warning);border-color:var(--warning);color:#fff}
    table{width:100%;border-collapse:collapse;font-size:.84rem}
    th{text-align:left;color:var(--text-secondary);font-weight:600;font-size:.72rem;text-transform:uppercase;letter-spacing:.05em;padding:9px 10px;border-bottom:1px solid var(--border)}
    td{padding:10px;border-bottom:1px solid var(--border);vertical-align:middle}
    tr:hover td{background:var(--bg)}
    .pill{display:inline-flex;align-items:center;gap:6px;padding:3px 10px;border-radius:999px;font-size:.72rem;font-weight:600;white-space:nowrap}
    .pill.g{background:var(--success-bg);color:#065f46;border:1px solid var(--success-border)}
    .pill.r{background:var(--danger-bg);color:#991b1b;border:1px solid var(--danger-border)}
    .pill.y{background:var(--warning-bg);color:#92400e;border:1px solid var(--warning-border)}
    .pill.b{background:var(--info-bg);color:#1e40af;border:1px solid var(--info-border)}
    .pill.i{background:#F3E8FF;color:#7c3aed;border:1px solid #DDD6FE}
    .pill.n{background:var(--bg);color:var(--text-secondary);border:1px solid var(--border)}
    .filters{display:flex;flex-wrap:wrap;gap:10px;align-items:center;margin-bottom:16px}
    .filters select,.filters input{background:var(--panel);border:1px solid var(--border);color:var(--text-strong);padding:8px 12px;border-radius:var(--radius-sm);font-size:.82rem;font-family:inherit;outline:none}
    .filters select:focus,.filters input:focus{border-color:var(--accent);box-shadow:0 0 0 3px rgba(37,99,235,0.1)}
    .filters input[type=text]{width:220px}
    .filters input[type=date]{padding:6px 10px}
    .modal{display:none;position:fixed;inset:0;background:rgba(15,23,42,.55);z-index:1050;align-items:flex-start;justify-content:center;padding:60px 16px;overflow-y:auto}
    .modal.open{display:flex}
    .modal-card{background:var(--panel);border:1px solid var(--border);border-radius:var(--radius-lg);max-width:480px;width:100%;padding:24px;box-shadow:var(--shadow-md)}
    .modal-card.wide{max-width:760px}
    .modal-card h4{font-size:1.02rem;font-weight:700;color:var(--text-strong);margin-bottom:16px}
    .field{margin-bottom:14px}
    .field label{display:block;font-size:.76rem;font-weight:600;color:var(--text-secondary);margin-bottom:5px}
    .field input,.field select,.field textarea{width:100%;background:#fff;border:1px solid var(--border);color:var(--text-strong);padding:9px 12px;border-radius:var(--radius-sm);font-size:.85rem;font-family:inherit;outline:none}
    .field input:focus,.field select:focus,.field textarea:focus{border-color:var(--accent);box-shadow:0 0 0 3px rgba(37,99,235,0.1)}
    .row{display:flex;gap:10px}
    .row>*{flex:1}
    .alert{padding:11px 14px;border-radius:var(--radius-sm);font-size:.84rem;margin-bottom:16px}
    .alert.ok{background:var(--success-bg);border:1px solid var(--success-border);color:#065f46}
    .alert.err{background:var(--danger-bg);border:1px solid var(--danger-border);color:#991b1b}
    .module-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(300px,1fr));gap:14px;margin-bottom:18px}
    .mod-card{background:var(--panel);border:1px solid var(--border);border-radius:var(--radius);padding:16px;position:relative;overflow:hidden;box-shadow:none}
    .mod-card::before{content:'';position:absolute;left:0;top:0;bottom:0;width:4px;background:var(--mc,var(--accent))}
    .mod-card.active{border-color:var(--accent);box-shadow:0 0 0 3px rgba(37,99,235,0.08)}
    .mod-card .m-top{display:flex;align-items:center;gap:12px}
    .mod-card .m-ic{width:40px;height:40px;border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:1.1rem;color:#fff;background:var(--mc,var(--accent));flex-shrink:0}
    .mod-card .m-name{font-weight:700;font-size:.93rem;color:var(--text-strong)}
    .mod-card .m-key{color:var(--text-secondary);font-size:.72rem;font-weight:600;text-transform:uppercase;letter-spacing:.04em;margin-top:2px}
    .mod-card .m-stats{display:flex;gap:14px;margin-top:14px;color:var(--text-secondary);font-size:.76rem}
    .mod-card .m-actions{display:flex;gap:8px;margin-top:14px}
    code{background:var(--accent-light);color:var(--accent);padding:2px 6px;border-radius:5px;font-size:.78rem}
    .dot{width:8px;height:8px;border-radius:50%;display:inline-block}
    .dot.on{background:var(--success);box-shadow:0 0 0 3px rgba(5,150,105,.18)}
    .dot.off{background:var(--border-strong)}
    .tabs{display:flex;gap:6px;margin-bottom:18px;flex-wrap:wrap}
    .tab{padding:7px 14px;border-radius:var(--radius-sm);border:1px solid var(--border);color:var(--text-secondary);font-size:.8rem;font-weight:600;cursor:pointer;background:var(--panel)}
    .tab:hover{background:var(--bg)}
    .tab.active{background:var(--accent);color:#fff;border-color:var(--accent)}
    .switch{position:relative;display:inline-block;width:42px;height:23px}
    .switch input{opacity:0;width:0;height:0}
    .slider{position:absolute;inset:0;border-radius:999px;background:var(--border-strong);transition:.15s;cursor:pointer}
    .slider:before{content:'';position:absolute;width:17px;height:17px;border-radius:50%;background:#fff;top:3px;left:3px;transition:.15s;box-shadow:0 1px 2px rgba(0,0,0,0.2)}
    .switch input:checked + .slider{background:var(--success)}
    .switch input:checked + .slider:before{transform:translateX(19px)}
    .text-sm{font-size:.78rem}
    .mono{font-family:'Consolas',monospace;font-size:.78rem;color:var(--accent)}
    .mt-2{margin-top:8px}.mt-3{margin-top:12px}.mt-4{margin-top:16px}
    .flex{display:flex;align-items:center;gap:8px}
    .jc-between{justify-content:space-between}
    .empty{padding:40px;text-align:center;color:var(--text-secondary);font-size:.85rem}
    a.link{color:var(--accent)}a.link:hover{text-decoration:underline}
    .sa-badge{display:inline-flex;align-items:center;gap:6px;padding:4px 10px;border-radius:999px;font-size:.72rem;font-weight:600;border:1px solid var(--border);color:var(--text-secondary);background:var(--panel)}
    .sa-badge.online{color:var(--success);border-color:var(--success-border);background:var(--success-bg)}
    .sa-badge.root{color:#92400e;border-color:var(--warning-border);background:var(--warning-bg)}
    @media(max-width:1000px){.g4{grid-template-columns:repeat(2,1fr)}.g3{grid-template-columns:1fr}.g2{grid-template-columns:1fr}}
    @media(max-width:640px){.g4{grid-template-columns:1fr}}
</style>
</head>
<body>
<button class="menu-toggle" id="menu-toggle">&#9776;</button>
<div class="sidebar-overlay" id="sidebar-overlay"></div>

<div class="app-shell">
    <aside class="sidebar" id="sidebar">
        <a href="dashboard.php" class="brand">
            <div class="brand-mark">SA</div>
            <div>
                <h1>Super Admin</h1>
                <p>Control Center</p>
            </div>
        </a>
        <nav class="nav">
            <span class="nav-section-label">Overview</span>
            <a class="<?= ($sa_active ?? '') === 'dashboard' ? 'active' : '' ?>" href="dashboard.php"><span class="nav-icon">▦</span> Dashboard</a>

            <span class="nav-section-label">Module Access</span>
            <a class="<?= ($sa_active ?? '') === 'submodules' ? 'active' : '' ?>" href="submodules.php"><span class="nav-icon">📋</span> All Submodules</a>
            <a class="<?= ($sa_active ?? '') === 'grant' ? 'active' : '' ?>" href="submodules.php?view=grant"><span class="nav-icon">🔓</span> Grant Access</a>
            <a class="<?= ($sa_active ?? '') === 'remove' ? 'active' : '' ?>" href="submodules.php?view=remove"><span class="nav-icon">➖</span> Remove Access</a>

            <span class="nav-section-label">Governance</span>
            <a class="<?= ($sa_active ?? '') === 'users' ? 'active' : '' ?>" href="users.php"><span class="nav-icon">👥</span> Users</a>
            <a class="<?= ($sa_active ?? '') === 'sessions' ? 'active' : '' ?>" href="sessions.php"><span class="nav-icon">◎</span> Live Sessions</a>
            <a class="<?= ($sa_active ?? '') === 'audit' ? 'active' : '' ?>" href="audit_logs.php"><span class="nav-icon">📜</span> Audit Logs</a>

            <div class="spacer"></div>
            <a href="<?= BASE_URL ?>logout.php" class="sidebar-logout-btn">Logout</a>
        </nav>
    </aside>

    <main class="content">
        <div class="topbar">
            <div>
                <span class="eyebrow">Super Admin Control</span>
                <h2><?= sa_es($sa_page_title ?? 'Control Center') ?></h2>
            </div>
            <div class="topbar-actions">
                <span class="sa-badge online">● <?= (int)$sa_online ?> online</span>
                <span class="sa-badge root">ROOT ACCESS</span>
                <div class="topbar-user-dropdown">
                    <button class="topbar-user-btn">
                        <span class="topbar-user-avatar"><?= sa_es(mb_substr($sa_user['full_name'] ?? 'SA', 0, 1)) ?></span>
                        <span class="topbar-user-name"><?= sa_es($sa_user['full_name'] ?? '') ?></span>
                        <span class="topbar-chevron">&#9662;</span>
                    </button>
                    <div class="topbar-dropdown-menu">
                        <a href="user_profile.php">My Profile</a>
                        <a href="<?= BASE_URL ?>logout.php">&#x2190; Logout</a>
                    </div>
                </div>
            </div>
        </div>
