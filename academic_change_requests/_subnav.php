<?php
$qs = http_build_query(['mode' => $mode, 'dept' => $dept_filter, 'session' => $session_filter, 'search' => $search]);
?>
<div class="panel mb-3">
    <div class="d-flex flex-wrap gap-2">
        <?php foreach ($acrModules as $key => $m): ?>
            <a class="btn btn-sm <?= $acrActive === $key ? 'btn-primary' : 'btn-outline-primary' ?>"
               href="<?= htmlspecialchars($m['file']); ?>?<?= htmlspecialchars($qs); ?>">
                <?= htmlspecialchars($m['title']); ?>
            </a>
        <?php endforeach; ?>
    </div>
</div>
