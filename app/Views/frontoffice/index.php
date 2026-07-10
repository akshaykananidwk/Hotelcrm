<div class="row g-3">
    <?php
    $panels = [
        ['Arrivals Today', $arrivals, 'checkin', 'Check-in', 'success'],
        ['Departures Today', $departures, 'checkout', 'Check-out', 'warning'],
    ];
    foreach ($panels as [$heading, $rows, $action, $label, $color]): ?>
        <div class="col-lg-6">
            <div class="card">
                <div class="card-header"><?= e($heading) ?> <span class="badge bg-secondary"><?= count($rows) ?></span></div>
                <div class="table-responsive"><table class="table mb-0 align-middle">
                    <tbody>
                    <?php if (!$rows): ?><tr><td class="text-muted py-3">Nothing scheduled.</td></tr><?php endif; ?>
                    <?php foreach ($rows as $r): ?>
                        <tr>
                            <td>
                                <a href="<?= url('/reservations/'.$r['id']) ?>"><?= e($r['guest_name']) ?></a><br>
                                <small class="text-muted"><?= e($r['code']) ?></small>
                            </td>
                            <td class="text-end">
                                <form method="post" action="<?= url('/frontoffice/'.$r['id'].'/'.$action) ?>">
                                    <?= csrf_field() ?>
                                    <button class="btn btn-sm btn-<?= $color ?>"><?= $label ?></button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table></div>
            </div>
        </div>
    <?php endforeach; ?>

    <div class="col-12">
        <div class="card">
            <div class="card-header">In-House Guests <span class="badge bg-primary"><?= count($inHouse) ?></span></div>
            <div class="table-responsive"><table class="table mb-0 align-middle">
                <thead><tr><th>Guest</th><th>Code</th><th>Check-out</th><th></th></tr></thead>
                <tbody>
                <?php if (!$inHouse): ?><tr><td colspan="4" class="text-muted py-3">No guests in-house.</td></tr><?php endif; ?>
                <?php foreach ($inHouse as $r): ?>
                    <tr>
                        <td><?= e($r['guest_name']) ?></td>
                        <td><a href="<?= url('/reservations/'.$r['id']) ?>"><?= e($r['code']) ?></a></td>
                        <td><?= dt($r['check_out']) ?></td>
                        <td class="text-end">
                            <form method="post" action="<?= url('/frontoffice/'.$r['id'].'/checkout') ?>">
                                <?= csrf_field() ?><button class="btn btn-sm btn-warning">Check-out</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table></div>
        </div>
    </div>
</div>
