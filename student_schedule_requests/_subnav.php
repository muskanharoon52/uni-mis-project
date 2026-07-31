<?php
$ssrQs = http_build_query([
    'student' => $ssr_search_student ?? '',
    'status' => $ssr_status ?? '',
    'dept' => $ssr_dept ?? 0,
    'session' => $ssr_session ?? 0,
    'semester' => $ssr_semester ?? 0,
    'program' => $ssr_program ?? 0,
]);
?>
<div class="panel mb-3">
    <div class="d-flex flex-wrap gap-2">
        <?php foreach ($ssrModules as $key => $m): ?>
            <a class="btn btn-sm <?= $ssrActive === $key ? 'btn-primary' : 'btn-outline-primary' ?>"
               href="<?= htmlspecialchars($m['file']); ?>?<?= htmlspecialchars($ssrQs); ?>">
                <?= htmlspecialchars($m['title']); ?>
            </a>
        <?php endforeach; ?>
    </div>
</div>
