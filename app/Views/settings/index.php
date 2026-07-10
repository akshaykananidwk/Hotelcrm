<div class="row g-3">
    <div class="col-lg-3">
        <div class="list-group">
            <?php foreach ($groups as $key => $meta): ?>
                <a href="<?= url('/settings/'.$key) ?>"
                   class="list-group-item list-group-item-action <?= $group===$key?'active':'' ?>">
                    <?= e($meta['title']) ?>
                </a>
            <?php endforeach; ?>
        </div>
        <form method="post" action="<?= url('/backup') ?>" class="mt-3">
            <?= csrf_field() ?>
            <button class="btn btn-outline-secondary w-100">Create Database Backup</button>
        </form>
    </div>
    <div class="col-lg-9">
        <div class="card">
            <div class="card-header"><?= e($groups[$group]['title']) ?></div>
            <div class="card-body">
                <?php if ($group === 'ota'): ?>
                    <?= \App\Core\View::renderPartial('settings/_ota', ['channels' => $channels]) ?>
                <?php elseif ($group === 'api'): ?>
                    <?= \App\Core\View::renderPartial('settings/_api', ['tokens' => $tokens]) ?>
                <?php else: ?>
                    <?= \App\Core\View::renderPartial('settings/_kv', ['group' => $group, 'values' => $values]) ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
