<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>University MIS</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', system-ui, -apple-system, sans-serif; background: #f4f5f8; min-height: 100vh; display: flex; align-items: center; justify-content: center; }
        .container { max-width: 800px; width: 100%; padding: 32px; }
        h1 { font-size: 2rem; font-weight: 800; color: #1a1d29; margin-bottom: 8px; letter-spacing: -0.03em; }
        .subtitle { color: #6b7280; font-size: 0.95rem; margin-bottom: 40px; }
        .grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 20px; }
        .card { background: #fff; border-radius: 14px; padding: 28px 24px; text-decoration: none; color: inherit; box-shadow: 0 1px 3px rgba(0,0,0,0.06); border: 1px solid #e5e7eb; transition: transform 0.15s, box-shadow 0.15s; }
        .card:hover { transform: translateY(-3px); box-shadow: 0 8px 24px rgba(0,0,0,0.08); }
        .card-icon { width: 48px; height: 48px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.4rem; margin-bottom: 16px; }
        .card h2 { font-size: 1.1rem; font-weight: 700; margin-bottom: 6px; color: #1a1d29; }
        .card p { font-size: 0.85rem; color: #6b7280; line-height: 1.5; }
        .icon-lms { background: #ede9fe; color: #7c3aed; }
        .icon-sbe { background: #eef2ff; color: #6366f1; }
        .icon-exam { background: #ecfdf5; color: #059669; }
    </style>
</head>
<body>
    <div class="container">
        <h1>University MIS</h1>
        <p class="subtitle">Select a module to continue</p>
        <div class="grid">
            <a class="card" href="LMS/">
                <div class="card-icon icon-lms">&#128218;</div>
                <h2>Learning Management</h2>
                <p>Course materials, assignments, grades, and student communications</p>
            </a>
            <a class="card" href="SBE_Module/">
                <div class="card-icon icon-sbe">&#128221;</div>
                <h2>System Based Examination</h2>
                <p>Question banks, exam creation, attempts, and result analytics</p>
            </a>
            <a class="card" href="Examination_Module/">
                <div class="card-icon icon-exam">&#127942;</div>
                <h2>Examination Module</h2>
                <p>Result viewing, exam scheduling, and semester promotions</p>
            </a>
        </div>
    </div>
</body>
</html>
