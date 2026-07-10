<form method="post" action="<?= url('/reservations') ?>">
    <?= csrf_field() ?>
    <div class="row g-3">
        <div class="col-lg-8">
            <div class="card mb-3">
                <div class="card-header">Stay Details</div>
                <div class="card-body row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Check-in</label>
                        <input type="date" name="check_in" class="form-control" required value="<?= date('Y-m-d') ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Check-out</label>
                        <input type="date" name="check_out" class="form-control" required value="<?= date('Y-m-d', strtotime('+1 day')) ?>">
                    </div>
                    <div class="col-md-4 d-flex align-items-end">
                        <span id="nightsDisplay" class="text-muted small"></span>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Adults</label>
                        <input type="number" name="adults" class="form-control" value="1" min="1">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Children</label>
                        <input type="number" name="children" class="form-control" value="0" min="0">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Source</label>
                        <select name="source" class="form-select">
                            <?php foreach (['walk_in','phone','website','corporate','travel_agent','group'] as $s): ?>
                                <option value="<?= $s ?>"><?= ucwords(str_replace('_',' ',$s)) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>

            <div class="card mb-3">
                <div class="card-header d-flex justify-content-between">Rooms
                    <button type="button" class="btn btn-sm btn-light" onclick="addRoomLine()">+ Add Room</button>
                </div>
                <div class="card-body">
                    <div id="roomLines"></div>
                </div>
            </div>

            <div class="card mb-3">
                <div class="card-header">Special Requests</div>
                <div class="card-body">
                    <textarea name="special_requests" class="form-control" rows="2"></textarea>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card">
                <div class="card-header">Guest</div>
                <div class="card-body">
                    <label class="form-label">Existing guest</label>
                    <select name="guest_id" class="form-select mb-3" onchange="toggleNewGuest(this.value)">
                        <option value="">— New guest —</option>
                        <?php foreach ($guests as $g): ?>
                            <option value="<?= $g['id'] ?>"><?= e(trim($g['first_name'].' '.$g['last_name'])) ?> · <?= e($g['phone']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <div id="newGuestFields">
                        <input name="guest_first_name" class="form-control mb-2" placeholder="First name">
                        <input name="guest_last_name" class="form-control mb-2" placeholder="Last name">
                        <input name="guest_phone" class="form-control mb-2" placeholder="Phone">
                        <input name="guest_email" type="email" class="form-control mb-2" placeholder="Email">
                    </div>
                    <select name="status" class="form-select mt-2">
                        <option value="confirmed">Confirmed</option>
                        <option value="pending">Pending</option>
                    </select>
                    <button type="submit" class="btn btn-primary w-100 mt-3">Create Reservation</button>
                </div>
            </div>
        </div>
    </div>
</form>

<template id="roomLineTpl">
    <div class="row g-2 mb-2 align-items-end room-line">
        <div class="col-6">
            <label class="form-label small">Room Type</label>
            <select name="room_type_id[]" class="form-select form-select-sm" onchange="fillRate(this)">
                <option value="">Select…</option>
                <?php foreach ($types as $t): ?>
                    <option value="<?= $t['id'] ?>" data-price="<?= $t['base_price'] ?>"><?= e($t['name']) ?> (<?= money($t['base_price']) ?>)</option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-4">
            <label class="form-label small">Rate / night</label>
            <input name="rate[]" type="number" step="0.01" class="form-control form-control-sm">
        </div>
        <div class="col-2">
            <input type="hidden" name="room_id[]" value="">
            <button type="button" class="btn btn-sm btn-outline-danger" onclick="this.closest('.room-line').remove()">✕</button>
        </div>
    </div>
</template>

<?php \App\Core\View::startSection('scripts'); ?>
<script>
function addRoomLine() {
    var tpl = document.getElementById('roomLineTpl').content.cloneNode(true);
    document.getElementById('roomLines').appendChild(tpl);
}
function fillRate(sel) {
    var price = sel.options[sel.selectedIndex].getAttribute('data-price');
    var rate = sel.closest('.room-line').querySelector('[name="rate[]"]');
    if (price && !rate.value) rate.value = price;
}
function toggleNewGuest(val) {
    document.getElementById('newGuestFields').style.display = val ? 'none' : 'block';
}
addRoomLine();
</script>
<?php \App\Core\View::endSection('scripts'); ?>
