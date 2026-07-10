<?php
/** Reusable pager. Expects $pg = ['page','pages'] and $baseUrl. */
if (($pg['pages'] ?? 1) <= 1) return;
$page = $pg['page'];
$pages = $pg['pages'];
$sep = str_contains($baseUrl, '?') ? '&' : '?';
?>
<nav class="mt-3">
    <ul class="pagination pagination-sm mb-0">
        <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
            <a class="page-link" href="<?= e($baseUrl . $sep . 'page=' . ($page - 1)) ?>">Prev</a>
        </li>
        <?php for ($i = max(1, $page - 2); $i <= min($pages, $page + 2); $i++): ?>
            <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                <a class="page-link" href="<?= e($baseUrl . $sep . 'page=' . $i) ?>"><?= $i ?></a>
            </li>
        <?php endfor; ?>
        <li class="page-item <?= $page >= $pages ? 'disabled' : '' ?>">
            <a class="page-link" href="<?= e($baseUrl . $sep . 'page=' . ($page + 1)) ?>">Next</a>
        </li>
    </ul>
</nav>
