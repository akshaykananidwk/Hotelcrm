<?php
namespace App\Controllers\Api;

use App\Core\Controller;
use App\Core\Request;
use App\Core\App;
use App\Core\RateLimiter;
use App\Models\Room;
use App\Models\RoomType;
use App\Models\Guest;
use App\Services\BookingService;
use App\Services\Notification\NotificationManager;

/**
 * Public website booking-engine endpoints. No bearer token (these back the
 * hotel's own booking widget) but rate-limited and CSRF-exempt as pure JSON.
 */
class PublicBookingController extends Controller
{
    private function hotelId(): int
    {
        $id = (int) Request::get('hotel_id', Request::input('hotel_id', 0));
        return $id ?: (int) App::db()->scalar('SELECT id FROM hotels ORDER BY id LIMIT 1');
    }

    public function availability(): void
    {
        header('Content-Type: application/json');
        if (!RateLimiter::allow('pub:' . Request::ip(), 120)) {
            $this->json(['error' => 'Too many requests'], 429);
            return;
        }
        $checkIn = Request::get('check_in');
        $checkOut = Request::get('check_out');
        if (!$checkIn || !$checkOut) {
            $this->json(['error' => 'check_in and check_out required'], 422);
            return;
        }
        $hotelId = $this->hotelId();
        $types = (new RoomType())->forHotel($hotelId);
        $roomModel = new Room();
        $out = [];
        foreach ($types as $type) {
            $available = $roomModel->available($hotelId, $checkIn, $checkOut, (int) $type['id']);
            $out[] = [
                'room_type_id' => (int) $type['id'],
                'name' => $type['name'],
                'base_price' => (float) $type['base_price'],
                'tax_rate' => (float) $type['tax_rate'],
                'available' => count($available),
            ];
        }
        $this->json(['success' => true, 'data' => $out]);
    }

    public function book(): void
    {
        header('Content-Type: application/json');
        if (!RateLimiter::allow('pub-book:' . Request::ip(), 20)) {
            $this->json(['error' => 'Too many requests'], 429);
            return;
        }
        $body = Request::all();
        foreach (['check_in', 'check_out', 'guest', 'rooms'] as $req) {
            if (empty($body[$req])) {
                $this->json(['error' => "$req is required"], 422);
                return;
            }
        }
        $hotelId = $this->hotelId();
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

        try {
            $reservation = (new BookingService())->create([
                'hotel_id' => $hotelId,
                'guest_id' => $guestId,
                'source' => 'website',
                'check_in' => $body['check_in'],
                'check_out' => $body['check_out'],
                'adults' => (int) ($body['adults'] ?? 1),
                'children' => (int) ($body['children'] ?? 0),
                'status' => 'confirmed',
            ], $body['rooms']);
        } catch (\Throwable $e) {
            $this->json(['error' => $e->getMessage()], 422);
            return;
        }
        (new NotificationManager())->sendReservationConfirmation((int) $reservation['id']);
        $this->json([
            'success' => true,
            'data' => ['code' => $reservation['code'], 'total' => $reservation['total_amount']],
        ], 201);
    }
}
