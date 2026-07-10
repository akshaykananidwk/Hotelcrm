<?php
namespace App\Controllers\Api;

use App\Core\Request;
use App\Core\Auth;
use App\Core\Audit;
use App\Models\Reservation;
use App\Models\Guest;
use App\Services\BookingService;
use App\Services\Notification\NotificationManager;

class BookingApiController extends ApiController
{
    public function index(): void
    {
        $result = (new Reservation())->withGuest($this->hotelId(), '1', [], max(1, (int) Request::get('page', 1)));
        $this->success($result);
    }

    public function show($params): void
    {
        $res = (new Reservation())->find($params['id']);
        if (!$res || (int) $res['hotel_id'] !== $this->hotelId()) {
            $this->error('Reservation not found.', 404);
            return;
        }
        $res['rooms'] = (new Reservation())->rooms((int) $res['id']);
        $this->success($res);
    }

    public function store(): void
    {
        $hotelId = $this->hotelId();
        $body = Request::all();
        foreach (['check_in', 'check_out', 'guest'] as $req) {
            if (empty($body[$req])) {
                $this->error("Field '$req' is required.", 422);
                return;
            }
        }
        // Upsert guest.
        $guest = $body['guest'];
        $guestModel = new Guest();
        $existing = !empty($guest['phone']) ? $guestModel->findBy('phone', $guest['phone']) : null;
        $guestId = $existing['id'] ?? $guestModel->create([
            'hotel_id' => $hotelId,
            'first_name' => $guest['first_name'] ?? 'Guest',
            'last_name' => $guest['last_name'] ?? null,
            'email' => $guest['email'] ?? null,
            'phone' => $guest['phone'] ?? null,
        ]);

        $lines = $body['rooms'] ?? [];
        try {
            $reservation = (new BookingService())->create([
                'hotel_id' => $hotelId,
                'guest_id' => $guestId,
                'source' => $body['source'] ?? 'website',
                'channel' => $body['channel'] ?? null,
                'check_in' => $body['check_in'],
                'check_out' => $body['check_out'],
                'adults' => (int) ($body['adults'] ?? 1),
                'children' => (int) ($body['children'] ?? 0),
                'special_requests' => $body['special_requests'] ?? null,
                'status' => 'confirmed',
                'created_by' => Auth::id(),
            ], $lines);
        } catch (\Throwable $e) {
            $this->error($e->getMessage(), 422);
            return;
        }
        Audit::log('api.booking.create', 'reservation', $reservation['id']);
        (new NotificationManager())->sendReservationConfirmation((int) $reservation['id']);
        $this->success($reservation, 201);
    }

    public function update($params): void
    {
        $res = (new Reservation())->find($params['id']);
        if (!$res || (int) $res['hotel_id'] !== $this->hotelId()) {
            $this->error('Reservation not found.', 404);
            return;
        }
        $data = [];
        foreach (['check_in', 'check_out', 'adults', 'children', 'status', 'special_requests'] as $f) {
            if (Request::input($f) !== null) {
                $data[$f] = Request::input($f);
            }
        }
        (new Reservation())->update($params['id'], $data);
        $this->success((new Reservation())->find($params['id']));
    }

    public function cancel($params): void
    {
        $res = (new Reservation())->find($params['id']);
        if (!$res || (int) $res['hotel_id'] !== $this->hotelId()) {
            $this->error('Reservation not found.', 404);
            return;
        }
        (new Reservation())->update($params['id'], ['status' => 'cancelled', 'cancelled_at' => date('Y-m-d H:i:s')]);
        Audit::log('api.booking.cancel', 'reservation', $params['id']);
        $this->success(['cancelled' => true]);
    }
}
