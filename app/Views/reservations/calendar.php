<?php
// Build a quick lookup: room_id => set of booked dates.
$booked = [];
foreach ($bookings as $b) {
    $cursor = strtotime($b['check_in']);
    $end = strtotime($b['check_out']);
    while ($cursor < $end) {
        $booked[$b['room_id']][date('Y-m-d', $cursor)] = $b['code'];
        $cursor += 86400;
    }
}
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0">14-Day Availability</h5>
    <div class="btn-group btn-group-sm">
        <a class="btn btn-light" href="<?= url('/reservations/calendar?start='.date('Y-m-d', strtotime($start.' -14 days'))) ?>">← Prev</a>
        <a class="btn btn-light" href="<?= url('/reservations/calendar') ?>">Today</a>
        <a class="btn btn-light" href="<?= url('/reservations/calendar?start='.date('Y-m-d', strtotime($start.' +14 days'))) ?>">Next →</a>
    </div>
</div>
<div class="card"><div class="table-responsive">
<table class="room-timeline">
    <thead><tr><th>Room</th>
        <?php foreach ($dates as $d): ?><th><?= date('D', strtotime($d)) ?><br><?= date('d/m', strtotime($d)) ?></th><?php endforeach; ?>
    </tr></thead>
    <tbody>
    <?php foreach ($rooms as $room): ?>
        <tr>
            <td class="text-start"><strong><?= e($room['number']) ?></strong><br><small class="text-muted"><?= e($room['type_name']) ?></small></td>
            <?php foreach ($dates as $d): ?>
                <?php $code = $booked[$room['id']][$d] ?? null; ?>
                <td class="<?= $code ? 'cell-booked' : '' ?>" title="<?= e($code ?? 'Available') ?>">
                    <?= $code ? '<small>'.e($code).'</small>' : '' ?>
                </td>
            <?php endforeach; ?>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
</div></div>
