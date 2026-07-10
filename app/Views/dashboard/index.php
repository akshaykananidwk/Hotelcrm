<?php
/** Dashboard: KPIs, charts, arrivals/departures. */
$cards = [
    ['Occupancy', $occupancy . '%', 'icon-bed', 'primary'],
    ["Today's Revenue", money($revenueToday), 'icon-money', 'success'],
    ['Month Revenue', money($monthRevenue), 'icon-chart', 'info'],
    ['Pending Payments', money($pendingPayments), 'icon-receipt', 'warning'],
];
?>
<div class="row g-3 mb-4">
    <?php foreach ($cards as [$label, $value, $icon, $color]): ?>
        <div class="col-sm-6 col-xl-3">
            <div class="card stat-card">
                <div class="card-body d-flex align-items-center gap-3">
                    <div class="stat-icon bg-<?= $color ?>-subtle text-<?= $color ?>"><span class="<?= $icon ?>"></span></div>
                    <div>
                        <div class="stat-value"><?= e($value) ?></div>
                        <div class="stat-label"><?= e($label) ?></div>
                    </div>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<div class="row g-3 mb-4">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                Revenue (last 7 days)
            </div>
            <div class="card-body">
                <canvas id="revChart" height="90"></canvas>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card h-100">
            <div class="card-header">Room Status</div>
            <div class="card-body">
                <?php
                $statusColors = ['available'=>'success','occupied'=>'primary','blocked'=>'dark','maintenance'=>'warning'];
                foreach ($roomStatus as $s => $count):
                    $pct = $totalRooms > 0 ? round($count / $totalRooms * 100) : 0; ?>
                    <div class="d-flex justify-content-between small mb-1">
                        <span class="text-capitalize"><?= e($s) ?></span><span><?= $count ?></span>
                    </div>
                    <div class="progress mb-3" style="height:6px">
                        <div class="progress-bar bg-<?= $statusColors[$s] ?>" style="width: <?= $pct ?>%"></div>
                    </div>
                <?php endforeach; ?>
                <div class="row text-center mt-3">
                    <div class="col"><div class="fw-bold text-success"><?= $housekeeping['clean'] ?></div><small class="text-muted">Clean</small></div>
                    <div class="col"><div class="fw-bold text-danger"><?= $housekeeping['dirty'] ?></div><small class="text-muted">Dirty</small></div>
                    <div class="col"><div class="fw-bold text-warning"><?= $housekeeping['maintenance'] ?></div><small class="text-muted">Maint.</small></div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-3">
    <?php
    $lists = [
        ['Today\'s Arrivals', $arrivals, 'check_in'],
        ['Today\'s Departures', $departures, 'check_out'],
        ['In-House Guests', $inHouse, 'check_out'],
    ];
    foreach ($lists as [$heading, $rows, $dateField]): ?>
        <div class="col-lg-4">
            <div class="card h-100">
                <div class="card-header"><?= e($heading) ?> <span class="badge bg-secondary"><?= count($rows) ?></span></div>
                <div class="list-group list-group-flush">
                    <?php if (!$rows): ?>
                        <div class="list-group-item text-muted small">Nothing here.</div>
                    <?php endif; ?>
                    <?php foreach (array_slice($rows, 0, 6) as $r): ?>
                        <a href="<?= url('/reservations/' . $r['id']) ?>" class="list-group-item d-flex justify-content-between align-items-center">
                            <span><?= e($r['guest_name']) ?><br><small class="text-muted"><?= e($r['code']) ?></small></span>
                            <span class="badge bg-<?= badge($r['status']) ?>"><?= dt($r[$dateField]) ?></span>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<?php \App\Core\View::startSection('scripts'); ?>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
(function () {
    var labels = <?= json_encode(array_column($trend, 'd')) ?>;
    var data = <?= json_encode(array_map('floatval', array_column($trend, 'total'))) ?>;
    var ctx = document.getElementById('revChart');
    if (ctx && window.Chart) {
        new Chart(ctx, {
            type: 'line',
            data: { labels: labels, datasets: [{
                label: 'Revenue', data: data, borderColor: '#0d6efd',
                backgroundColor: 'rgba(13,110,253,.12)', fill: true, tension: .35 }] },
            options: { plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true } } }
        });
    }
})();
</script>
<?php \App\Core\View::endSection('scripts'); ?>
