<div class="row g-3">
    <div class="col-lg-8">
        <div class="card mb-3">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><?= e($res['code']) ?> <span class="badge bg-<?= badge($res['status']) ?>"><?= e(str_replace('_',' ',$res['status'])) ?></span></span>
                <div class="d-flex gap-2">
                    <?php if (can('frontoffice.manage') && $res['status'] === 'confirmed'): ?>
                        <form method="post" action="<?= url('/frontoffice/'.$res['id'].'/checkin') ?>"><?= csrf_field() ?>
                            <button class="btn btn-sm btn-success">Check-in</button></form>
                    <?php endif; ?>
                    <?php if (can('frontoffice.manage') && $res['status'] === 'checked_in'): ?>
                        <form method="post" action="<?= url('/frontoffice/'.$res['id'].'/checkout') ?>"><?= csrf_field() ?>
                            <button class="btn btn-sm btn-warning">Check-out</button></form>
                    <?php endif; ?>
                    <a href="<?= url('/billing/create?reservation_id='.$res['id']) ?>" class="btn btn-sm btn-primary">Create Invoice</a>
                </div>
            </div>
            <div class="card-body row g-3">
                <div class="col-md-3"><small class="text-muted d-block">Check-in</small><?= dt($res['check_in']) ?></div>
                <div class="col-md-3"><small class="text-muted d-block">Check-out</small><?= dt($res['check_out']) ?></div>
                <div class="col-md-3"><small class="text-muted d-block">Guests</small><?= (int)$res['adults'] ?> adults, <?= (int)$res['children'] ?> children</div>
                <div class="col-md-3"><small class="text-muted d-block">Source</small><span class="text-capitalize"><?= e(str_replace('_',' ',$res['source'])) ?></span></div>
                <?php if ($res['special_requests']): ?>
                    <div class="col-12"><small class="text-muted d-block">Special requests</small><?= e($res['special_requests']) ?></div>
                <?php endif; ?>
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-header">Rooms</div>
            <div class="table-responsive"><table class="table mb-0">
                <thead><tr><th>Type</th><th>Room</th><th>Rate</th><th>Nights</th><th>Tax %</th><th></th></tr></thead>
                <tbody>
                <?php foreach ($rooms as $rr): ?>
                    <tr>
                        <td><?= e($rr['type_name']) ?></td>
                        <td><?= $rr['room_number'] ? e($rr['room_number']) : '<span class="text-muted">Unassigned</span>' ?></td>
                        <td><?= money($rr['rate']) ?></td>
                        <td><?= (int)$rr['nights'] ?></td>
                        <td><?= e($rr['tax_rate']) ?>%</td>
                        <td>
                            <?php if (can('frontoffice.manage') && $res['status'] === 'checked_in'): ?>
                            <form method="post" action="<?= url('/frontoffice/'.$res['id'].'/room-change') ?>" class="d-flex gap-1">
                                <?= csrf_field() ?>
                                <input type="hidden" name="reservation_room_id" value="<?= $rr['id'] ?>">
                                <select name="room_id" class="form-select form-select-sm">
                                    <?php foreach ($availableRooms as $ar): ?>
                                        <option value="<?= $ar['id'] ?>"><?= e($ar['number']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <button class="btn btn-sm btn-light">Move</button>
                            </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table></div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card mb-3">
            <div class="card-header">Guest</div>
            <div class="card-body">
                <h6><a href="<?= url('/guests/'.$guest['id']) ?>"><?= e(trim($guest['first_name'].' '.$guest['last_name'])) ?></a></h6>
                <div class="small text-muted">📞 <?= e($guest['phone']) ?></div>
                <div class="small text-muted">✉ <?= e($guest['email'] ?: '—') ?></div>
            </div>
        </div>
        <div class="card">
            <div class="card-header">Payment Summary</div>
            <div class="card-body">
                <div class="d-flex justify-content-between"><span>Total</span><strong><?= money($res['total_amount']) ?></strong></div>
                <div class="d-flex justify-content-between text-success"><span>Paid</span><span><?= money($res['paid_amount']) ?></span></div>
                <div class="d-flex justify-content-between text-danger"><span>Balance</span><span><?= money($res['total_amount'] - $res['paid_amount']) ?></span></div>
                <?php if (can('reservations.manage') && !in_array($res['status'], ['cancelled','checked_out'])): ?>
                <hr>
                <form method="post" action="<?= url('/reservations/'.$res['id'].'/cancel') ?>"
                      onsubmit="return confirm('Cancel this reservation?')">
                    <?= csrf_field() ?>
                    <button class="btn btn-sm btn-outline-danger w-100">Cancel Reservation</button>
                </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
