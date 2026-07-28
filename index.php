<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
session_start();
session_unset();
session_destroy();
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <title>University MIS</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', system-ui, -apple-system, sans-serif; background: #0f172a; color: #fff; min-height: 100vh; overflow-x: hidden; }
        .hero { position: relative; min-height: 100vh; display: flex; align-items: center; justify-content: center; background: url('cusit.jpeg') center/cover no-repeat; }
        .hero::before { content: ''; position: absolute; inset: 0; background: rgba(15, 23, 42, 0.82); backdrop-filter: blur(6px); -webkit-backdrop-filter: blur(6px); }
        .hero::after { content: ''; position: absolute; width: 700px; height: 700px; border-radius: 50%; background: radial-gradient(circle, rgba(99,102,241,0.18), transparent 60%); top: -20%; right: -10%; pointer-events: none; }
        .hero > * { position: relative; z-index: 1; }
        .hero-content { text-align: center; max-width: 1200px; padding: 0 24px; }
        .hero-content h1 { font-size: clamp(2.2rem, 5vw, 3.5rem); font-weight: 800; letter-spacing: -.03em; margin-bottom: 16px; background: linear-gradient(135deg, #fff 0%, #c7d2fe 50%, #818cf8 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text; }
        .hero-content p { font-size: 1.05rem; color: rgba(255,255,255,0.6); max-width: 55ch; margin: 0 auto 40px; line-height: 1.6; }
        .hero-badge { display: inline-flex; align-items: center; gap: 8px; padding: 8px 18px; border-radius: 99px; background: rgba(255,255,255,0.08); border: 1px solid rgba(255,255,255,0.1); font-size: .82rem; color: rgba(255,255,255,0.7); margin-bottom: 24px; }
        .hero-badge .dot { width: 8px; height: 8px; border-radius: 50%; background: #22c55e; animation: pulse 2s infinite; }
        @keyframes pulse { 0%, 100% { opacity: 1; } 50% { opacity: .4; } }
        .module-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 24px; max-width: 1200px; margin: 0 auto; }
        .module-card { background: rgba(255,255,255,0.08); backdrop-filter: blur(12px); -webkit-backdrop-filter: blur(12px); border: 1px solid rgba(255,255,255,0.1); border-radius: 16px; padding: 28px 24px; transition: all .2s ease; cursor: pointer; text-decoration: none; color: #fff; display: block; box-shadow: 0 4px 20px rgba(0,0,0,0.2); text-align: center; }
        .module-card:hover { background: rgba(255,255,255,0.14); transform: translateY(-4px); box-shadow: 0 12px 40px rgba(0,0,0,0.3); border-color: rgba(255,255,255,0.2); }
        .module-card-header { display: flex; align-items: center; gap: 14px; margin-bottom: 10px; }
        .module-icon { width: 48px; height: 48px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.4rem; flex-shrink: 0; }
        .module-card-header h3 { font-size: 1.05rem; font-weight: 700; margin: 0; color: #fff; }
        .module-card-header h3 + p { font-size: .78rem; color: rgba(255,255,255,0.5); margin: 2px 0 0; line-height: 1.3; }
        .module-card > p:last-of-type { font-size: .84rem; color: rgba(255,255,255,0.6); line-height: 1.5; margin: 0 0 14px; }
        .module-card-footer { display: flex; align-items: center; gap: 8px; margin-top: 16px; padding-top: 14px; border-top: 1px solid rgba(255,255,255,0.08); }
        .status-dot { width: 7px; height: 7px; border-radius: 50%; background: #22c55e; }
        .status-text { font-size: .72rem; color: rgba(255,255,255,0.5); font-weight: 600; text-transform: uppercase; letter-spacing: .08em; }
        .icon-lms { background: rgba(99,102,241,0.15); color: #818cf8; }
        .icon-sbe { background: rgba(245,158,11,0.15); color: #fbbf24; }
        .icon-adm { background: rgba(16,185,129,0.15); color: #34d399; }
        .icon-exm { background: rgba(239,68,68,0.15); color: #f87171; }
        .icon-fin { background: rgba(59,130,246,0.15); color: #60a5fa; }
        .icon-sso { background: rgba(168,85,247,0.15); color: #c084fc; }
        .footer { text-align: center; padding: 32px 24px; color: rgba(255,255,255,0.5); font-size: .78rem; }
    </style>
</head>
<body>
    <section class="hero">
        <div class="hero-content">
            <div class="hero-badge"><span class="dot"></span> All Systems Operational</div>
            <h1>University Management Information System</h1>
            <p>Centralized platform for learning management, examinations, admissions, finance, and centralized records — all in one secure portal.</p>
            <div class="module-grid">
                <a class="module-card" href="modules/lms/public/login.php">
                    <div class="module-card-header"><div class="module-icon icon-lms">&#128218;</div><div><h3>LMS</h3><p>Learning Management System</p></div></div>
                    <p>Courses, attendance, assignments, grades, and student communications.</p>
                    <div class="module-card-footer"><span class="status-dot"></span><span class="status-text">Active</span></div>
                </a>
                <a class="module-card" href="modules/sbe/login.php">
                    <div class="module-card-header"><div class="module-icon icon-sbe">&#128200;</div><div><h3>SBE</h3><p>System Based Examination</p></div></div>
                    <p>Exam scheduling, question banks, student answers, and grade management.</p>
                    <div class="module-card-footer"><span class="status-dot"></span><span class="status-text">Active</span></div>
                </a>
                <a class="module-card" href="modules/admission/auth/login.php">
                    <div class="module-card-header"><div class="module-icon icon-adm">&#127891;</div><div><h3>Admission</h3><p>Admission Management</p></div></div>
                    <p>Applications, student enrollment, fees, scholarships, and reports.</p>
                    <div class="module-card-footer"><span class="status-dot"></span><span class="status-text">Active</span></div>
                </a>
                <a class="module-card" href="examination/login.php">
                    <div class="module-card-header"><div class="module-icon icon-exm">&#128209;</div><div><h3>Examination</h3><p>Exam & Results</p></div></div>
                    <p>Exam scheduling, result publishing, grade management, and promotion.</p>
                    <div class="module-card-footer"><span class="status-dot"></span><span class="status-text">Active</span></div>
                </a>
                <a class="module-card" href="modules/finance/login.php">
                    <div class="module-card-header"><div class="module-icon icon-fin">&#128176;</div><div><h3>Finance</h3><p>Finance Management</p></div></div>
                    <p>Fee structures, billing, payments, receipts, and financial reports.</p>
                    <div class="module-card-footer"><span class="status-dot"></span><span class="status-text">Active</span></div>
                </a>
                <a class="module-card" href="modules/sso/login.php">
                    <div class="module-card-header"><div class="module-icon icon-sso">&#128273;</div><div><h3>SSO Admin</h3><p>Centralized Auth</p></div></div>
                    <p>Single sign-on for all modules with role-based access control.</p>
                    <div class="module-card-footer"><span class="status-dot"></span><span class="status-text">Active</span></div>
                </a>
            </div>
        </div>
    </section>
    <div class="footer">University MIS &copy; <?= date('Y') ?> — All rights reserved</div>
</body>
</html>