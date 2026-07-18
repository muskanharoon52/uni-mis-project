<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';

$user = require_role('teacher');
$active = 'timetable';
$pageTitle = 'Timetable';

require_once __DIR__ . '/../includes/header.php';
?>
<div class="coming-soon-card">
    <div class="coming-soon-icon">
        <svg xmlns="http://www.w3.org/2000/svg" width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" class="animate-spin-slow"><circle cx="12" cy="12" r="10"></circle><path d="M12 6v6l4 2"></path></svg>
    </div>
    <h1><?= e($pageTitle) ?></h1>
    <p class="muted">This section is currently under active development and will be connected in a future update.</p>
    <div class="progress-bar-container">
        <div class="progress-bar-fill animate-progress"></div>
    </div>
    <span class="status-badge">System Integration Pending</span>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
