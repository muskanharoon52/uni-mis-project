<?php
if (!defined('SA_INIT')) {
    require_once __DIR__ . '/auth.php';
    sa_guard();
}
$sa_user = sa_current_user();
$sa_online = count(sa_scan_sessions());
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= sa_es($sa_page_title ?? 'Super Admin Control') ?> | Super Admin</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<style>
:root{
  --bg:#0b1020; --panel:#111832; --panel2:#0e1529; --border:#1f2945;
  --text:#e6e9f5; --muted:#8b95b8; --indigo:#6366f1; --indigo2:#4f46e5;
  --green:#22c55e; --red:#ef4444; --amber:#f59e0b; --sky:#38bdf8;
}
*{margin:0;padding:0;box-sizing:border-box}
body{font-family:'Inter',system-ui,sans-serif;background:var(--bg);color:var(--text);font-size:.9rem}
a{color:inherit;text-decoration:none}
.layout{display:flex;min-height:100vh}
.sidebar{width:250px;background:var(--panel);border-right:1px solid var(--border);position:fixed;top:0;bottom:0;left:0;display:flex;flex-direction:column;z-index:50}
.sidebar .brand{padding:22px 20px;border-bottom:1px solid var(--border);display:flex;align-items:center;gap:12px}
.brand .logo{width:38px;height:38px;border-radius:11px;background:linear-gradient(135deg,var(--indigo),var(--indigo2));display:flex;align-items:center;justify-content:center;font-size:1.15rem;font-weight:800;color:#fff;flex-shrink:0}
.brand .t{font-weight:800;font-size:.98rem;letter-spacing:-.01em;line-height:1.15}
.brand .s{font-size:.68rem;color:var(--muted);font-weight:600;text-transform:uppercase;letter-spacing:.06em}
.nav{padding:14px 12px;flex:1;overflow-y:auto}
.nav .label{font-size:.65rem;font-weight:700;letter-spacing:.1em;text-transform:uppercase;color:var(--muted);padding:10px 10px 6px}
.nav a{display:flex;align-items:center;gap:11px;padding:9px 11px;border-radius:9px;color:#c3cadf;font-weight:500;margin-bottom:2px;transition:background .12s,color .12s}
.nav a:hover{background:rgba(99,102,241,.12);color:#fff}
.nav a.active{background:linear-gradient(135deg,rgba(99,102,241,.25),rgba(79,70,229,.18));color:#fff;font-weight:600}
.nav a .ic{width:18px;text-align:center;font-size:.95rem;flex-shrink:0}
.sidebar .foot{padding:14px;border-top:1px solid var(--border)}
.sidebar .foot .me{display:flex;align-items:center;gap:10px}
.avatar{width:34px;height:34px;border-radius:50%;background:linear-gradient(135deg,#f59e0b,#f97316);display:flex;align-items:center;justify-content:center;font-weight:800;font-size:.85rem;color:#fff;flex-shrink:0}
.main{flex:1;margin-left:250px;display:flex;flex-direction:column;min-height:100vh}
.topbar{position:sticky;top:0;z-index:40;background:rgba(11,16,32,.85);backdrop-filter:blur(10px);border-bottom:1px solid var(--border);padding:0 26px;height:62px;display:flex;align-items:center;gap:16px}
.topbar .crumbs{font-size:.8rem;color:var(--muted)}
.topbar .crumbs b{color:var(--text);font-weight:600}
.topbar .spacer{flex:1}
.badge{font-size:.72rem;font-weight:600;padding:4px 10px;border-radius:999px;border:1px solid var(--border);color:var(--muted);background:var(--panel2)}
.badge.online{color:var(--green);border-color:rgba(34,197,94,.3);background:rgba(34,197,94,.1)}
.badge.admin{color:#fbbf24;border-color:rgba(245,158,11,.35);background:rgba(245,158,11,.12)}
.content{padding:26px;flex:1}
.page-title{font-size:1.25rem;font-weight:800;letter-spacing:-.02em;margin-bottom:4px}
.page-sub{color:var(--muted);font-size:.84rem;margin-bottom:22px}
.card{background:var(--panel);border:1px solid var(--border);border-radius:14px;padding:18px;margin-bottom:18px}
.card h4{font-size:.95rem;font-weight:700;margin-bottom:14px;display:flex;align-items:center;gap:8px}
.grid{display:grid;gap:16px}
.g4{grid-template-columns:repeat(4,1fr)}
.g3{grid-template-columns:repeat(3,1fr)}
.g2{grid-template-columns:repeat(2,1fr)}
.kpi{background:var(--panel);border:1px solid var(--border);border-radius:14px;padding:18px}
.kpi .k-top{display:flex;align-items:center;justify-content:space-between}
.kpi .k-ic{width:38px;height:38px;border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:1.05rem}
.kpi .k-val{font-size:1.7rem;font-weight:800;margin-top:10px;letter-spacing:-.02em}
.kpi .k-lbl{color:var(--muted);font-size:.78rem;font-weight:600;margin-top:2px}
.btn{display:inline-flex;align-items:center;gap:6px;padding:9px 14px;border-radius:9px;border:1px solid var(--border);background:var(--panel2);color:var(--text);font-size:.82rem;font-weight:600;cursor:pointer;font-family:inherit;transition:opacity .12s}
.btn:hover{opacity:.88}
.btn-primary{background:linear-gradient(135deg,var(--indigo),var(--indigo2));border-color:transparent;color:#fff}
.btn-danger{background:rgba(239,68,68,.15);border-color:rgba(239,68,68,.35);color:#f87171}
.btn-warn{background:rgba(245,158,11,.15);border-color:rgba(245,158,11,.35);color:#fbbf24}
.btn-sm{padding:5px 10px;font-size:.74rem}
.btn-ghost{background:transparent}
table{width:100%;border-collapse:collapse;font-size:.84rem}
th{text-align:left;color:var(--muted);font-weight:600;font-size:.72rem;text-transform:uppercase;letter-spacing:.05em;padding:9px 10px;border-bottom:1px solid var(--border)}
td{padding:10px;border-bottom:1px solid rgba(31,41,69,.6);vertical-align:middle}
tr:hover td{background:rgba(99,102,241,.04)}
.pill{display:inline-flex;align-items:center;gap:6px;padding:3px 10px;border-radius:999px;font-size:.72rem;font-weight:600}
.pill.g{background:rgba(34,197,94,.12);color:#4ade80;border:1px solid rgba(34,197,94,.3)}
.pill.r{background:rgba(239,68,68,.12);color:#f87171;border:1px solid rgba(239,68,68,.3)}
.pill.y{background:rgba(245,158,11,.12);color:#fbbf24;border:1px solid rgba(245,158,11,.3)}
.pill.b{background:rgba(56,189,248,.12);color:#38bdf8;border:1px solid rgba(56,189,248,.3)}
.pill.i{background:rgba(139,92,246,.12);color:#a78bfa;border:1px solid rgba(139,92,246,.3)}
.pill.n{background:rgba(139,149,184,.12);color:#8b95b8;border:1px solid rgba(139,149,184,.3)}
.filters{display:flex;flex-wrap:wrap;gap:10px;align-items:center;margin-bottom:16px}
.filters select,.filters input{background:var(--panel2);border:1px solid var(--border);color:var(--text);padding:8px 12px;border-radius:9px;font-size:.82rem;font-family:inherit;outline:none}
.filters input[type=text]{width:220px}
.filters input[type=date]{padding:6px 10px}
.modal{display:none;position:fixed;inset:0;background:rgba(0,0,0,.65);z-index:100;align-items:flex-start;justify-content:center;padding:60px 16px;overflow-y:auto}
.modal.open{display:flex}
.modal-card{background:var(--panel);border:1px solid var(--border);border-radius:16px;max-width:480px;width:100%;padding:24px}
.modal-card.wide{max-width:760px}
.modal-card h4{font-size:1.02rem;font-weight:700;margin-bottom:16px}
.field{margin-bottom:14px}
.field label{display:block;font-size:.76rem;font-weight:600;color:var(--muted);margin-bottom:5px}
.field input,.field select,.field textarea{width:100%;background:var(--panel2);border:1px solid var(--border);color:var(--text);padding:9px 12px;border-radius:9px;font-size:.85rem;font-family:inherit;outline:none}
.field input:focus,.field select:focus{border-color:var(--indigo)}
.row{display:flex;gap:10px}
.row>*{flex:1}
.alert{padding:11px 14px;border-radius:10px;font-size:.84rem;margin-bottom:16px}
.alert.ok{background:rgba(34,197,94,.12);border:1px solid rgba(34,197,94,.3);color:#4ade80}
.alert.err{background:rgba(239,68,68,.12);border:1px solid rgba(239,68,68,.3);color:#f87171}
.module-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(300px,1fr));gap:14px}
.mod-card{background:var(--panel);border:1px solid var(--border);border-radius:14px;padding:16px;position:relative;overflow:hidden}
.mod-card::before{content:'';position:absolute;left:0;top:0;bottom:0;width:4px;background:var(--mc,#6366f1)}
.mod-card .m-top{display:flex;align-items:center;gap:12px}
.mod-card .m-ic{width:40px;height:40px;border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:1.1rem;color:#fff;background:var(--mc,#6366f1);flex-shrink:0}
.mod-card .m-name{font-weight:700;font-size:.93rem}
.mod-card .m-key{color:var(--muted);font-size:.72rem;font-weight:600;text-transform:uppercase;letter-spacing:.04em;margin-top:2px}
.mod-card .m-stats{display:flex;gap:14px;margin-top:14px;color:var(--muted);font-size:.76rem}
.mod-card .m-actions{display:flex;gap:8px;margin-top:14px}
code{background:rgba(99,102,241,.12);color:#a5b4fc;padding:2px 6px;border-radius:5px;font-size:.78rem}
.dot{width:8px;height:8px;border-radius:50%;display:inline-block}
.dot.on{background:var(--green);box-shadow:0 0 0 3px rgba(34,197,94,.18)}
.dot.off{background:var(--muted)}
.tabs{display:flex;gap:6px;margin-bottom:18px;flex-wrap:wrap}
.tab{padding:7px 14px;border-radius:9px;border:1px solid var(--border);color:var(--muted);font-size:.8rem;font-weight:600;cursor:pointer}
.tab.active{background:linear-gradient(135deg,var(--indigo),var(--indigo2));color:#fff;border-color:transparent}
.switch{position:relative;display:inline-block;width:42px;height:23px}
.switch input{opacity:0;width:0;height:0}
.slider{position:absolute;inset:0;border-radius:999px;background:#263252;transition:.15s;cursor:pointer}
.slider:before{content:'';position:absolute;width:17px;height:17px;border-radius:50%;background:#c3cadf;top:3px;left:3px;transition:.15s}
.switch input:checked + .slider{background:var(--indigo)}
.switch input:checked + .slider:before{transform:translateX(19px);background:#fff}
.muted{color:var(--muted)}
.text-sm{font-size:.78rem}
.mono{font-family:'Consolas',monospace;font-size:.78rem;color:#a5b4fc}
.mt-2{margin-top:8px}.mt-3{margin-top:12px}.mt-4{margin-top:16px}
.flex{display:flex;align-items:center;gap:8px}
.jc-between{justify-content:space-between}
.empty{padding:40px;text-align:center;color:var(--muted);font-size:.85rem}
a.link{color:#a5b4fc}.link:hover{text-decoration:underline}
@media(max-width:1000px){.g4{grid-template-columns:repeat(2,1fr)}.g3{grid-template-columns:1fr}.g2{grid-template-columns:1fr}}
@media(max-width:760px){.sidebar{transform:translateX(-100%)}.main{margin-left:0}.g4{grid-template-columns:1fr}}
</style>
</head>
<body>
<div class="layout">
  <aside class="sidebar">
    <div class="brand">
      <div class="logo">SA</div>
      <div>
        <div class="t">Super Admin</div>
        <div class="s">Control Center</div>
      </div>
    </div>
    <nav class="nav">
      <div class="label">Overview</div>
      <a href="dashboard.php" class="<?= ($sa_active ?? '') === 'dashboard' ? 'active' : '' ?>"><span class="ic">▦</span> Dashboard</a>
      <a href="modules.php" class="<?= ($sa_active ?? '') === 'modules' ? 'active' : '' ?>"><span class="ic">◫</span> Modules</a>
      <a href="submodules.php" class="<?= ($sa_active ?? '') === 'submodules' ? 'active' : '' ?>"><span class="ic">▤</span> Submodules</a>
      <div class="label">Access Control</div>
      <a href="permissions.php" class="<?= ($sa_active ?? '') === 'permissions' ? 'active' : '' ?>"><span class="ic">⚙</span> Permissions</a>
      <div class="label">Governance</div>
      <a href="users.php" class="<?= ($sa_active ?? '') === 'users' ? 'active' : '' ?>"><span class="ic">👥</span> Users</a>
      <a href="sessions.php" class="<?= ($sa_active ?? '') === 'sessions' ? 'active' : '' ?>"><span class="ic">◎</span> Live Sessions</a>
      <a href="audit_logs.php" class="<?= ($sa_active ?? '') === 'audit' ? 'active' : '' ?>"><span class="ic">📜</span> Audit Logs</a>
    </nav>
    <div class="foot">
      <div class="me">
        <div class="avatar"><?= sa_es(mb_substr($sa_user['full_name'] ?? 'SA', 0, 1)) ?></div>
        <div style="min-width:0">
          <div style="font-weight:600;font-size:.82rem;white-space:nowrap;overflow:hidden;text-overflow:ellipsis"><?= sa_es($sa_user['full_name'] ?? '') ?></div>
          <div style="font-size:.7rem;color:var(--muted)"><?= sa_es($sa_user['username'] ?? '') ?></div>
        </div>
      </div>
      <div style="display:flex;gap:8px;margin-top:12px">
        <a class="btn btn-sm" href="/uni-mis-project/dashboard.php" title="Open portal">Portal</a>
        <a class="btn btn-sm btn-danger" href="/uni-mis-project/logout.php">Logout</a>
      </div>
    </div>
  </aside>
  <main class="main">
    <div class="topbar">
      <div class="crumbs">Super Admin / <b><?= sa_es($sa_page_title ?? 'Control Center') ?></b></div>
      <div class="spacer"></div>
      <span class="badge online">● <?= (int)$sa_online ?> online</span>
      <span class="badge admin">ROOT ACCESS</span>
    </div>
    <div class="content">
