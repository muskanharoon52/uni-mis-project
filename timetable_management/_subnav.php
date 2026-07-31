<?php
$qs = http_build_query(array_filter([
    'dept' => $_GET['dept'] ?? '', 'program' => $_GET['program'] ?? '',
    'session' => $_GET['session'] ?? '', 'semester' => $_GET['semester'] ?? '',
    'section' => $_GET['section'] ?? '',
]));
?>
<div class="panel mb-3">
    <div class="d-flex flex-wrap gap-2">
        <?php foreach ($ttModules as $key => $m): ?>
            <a class="btn btn-sm <?= $ttActive === $key ? 'btn-primary' : 'btn-outline-primary' ?>"
               href="<?= htmlspecialchars($m['file']); ?><?= $qs !== '' ? '?' . htmlspecialchars($qs) : ''; ?>">
                <?= htmlspecialchars($m['title']); ?>
            </a>
        <?php endforeach; ?>
    </div>
</div>
