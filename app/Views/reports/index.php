<h5 class="mb-3">Reports</h5>
<div class="row g-3">
    <?php foreach ($available as $key => $label): ?>
        <div class="col-6 col-md-4 col-lg-3">
            <a href="<?= url('/reports/'.$key) ?>" class="card text-decoration-none h-100">
                <div class="card-body">
                    <div class="stat-icon bg-primary-subtle text-primary mb-2"><span class="icon-chart"></span></div>
                    <h6 class="mb-0"><?= e($label) ?></h6>
                    <small class="text-muted">View & export</small>
                </div>
            </a>
        </div>
    <?php endforeach; ?>
</div>
