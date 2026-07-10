<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Session;
use App\Core\Auth;
use App\Core\Audit;
use App\Core\App;
use App\Models\Reservation;
use App\Models\Room;
use App\Models\RoomType;
use App\Models\Guest;
use App\Services\BookingService;
use App\Services\Notification\NotificationManager;

class ReservationController extends Controller
{
    private Reservation $reservations;

    public function __construct()
    {
        $this->reservations = new Reservation();
    }

    public function index(): void
    {
        $this->authorize('reservations.view');
        $hotelId = $this->currentHotelId();
        $status = Request::get('status');
        $where = '1';
        $params = [];
        if ($status) {
            $where = 'r.status = ?';
            $params[] = $status;
        }
        $result = $this->reservations->withGuest($hotelId, $where, $params, $this->page());
        $this->view('reservations/index', [
            'title' => 'Reservations',
            'reservations' => $result['data'],
            'pg' => $result,
            'status' => $status,
        ]);
    }

    public function create(): void
    {
        $this->authorize('reservations.manage');
        $hotelId = $this->currentHotelId();
        $this->view('reservations/form', [
            'title' => 'New Reservation',
            'types' => (new RoomType())->forHotel($hotelId),
            'guests' => (new Guest())->all('hotel_id = ?', [$hotelId], 'first_name ASC'),
        ]);
    }

    public function store(): void
    {
        $this->authorize('reservations.manage');
        $hotelId = $this->currentHotelId();
        $data = $this->validate([
            'check_in' => 'required|date',
            'check_out' => 'required|date',
            'adults' => 'required|int|min:1',
        ]);

        // Resolve or create the guest.
        $guestId = (int) Request::input('guest_id');
        if (!$guestId) {
            $guestModel = new Guest();
            $guestId = $guestModel->create([
                'hotel_id' => $hotelId,
                'first_name' => Request::input('guest_first_name', 'Guest'),
                'last_name' => Request::input('guest_last_name'),
                'email' => Request::input('guest_email'),
                'phone' => Request::input('guest_phone'),
            ]);
        }

        $lines = [];
        foreach ((array) Request::input('room_type_id', []) as $i => $typeId) {
            if (!$typeId) continue;
            $lines[] = [
                'room_type_id' => (int) $typeId,
                'room_id' => Request::input('room_id')[$i] ?? null,
                'rate' => Request::input('rate')[$i] ?? null,
            ];
        }

        try {
            $reservation = (new BookingService())->create([
                'hotel_id' => $hotelId,
                'guest_id' => $guestId,
                'source' => Request::input('source', 'walk_in'),
                'check_in' => $data['check_in'],
                'check_out' => $data['check_out'],
                'adults' => (int) $data['adults'],
                'children' => (int) Request::input('children', 0),
                'special_requests' => Request::input('special_requests'),
                'status' => Request::input('status', 'confirmed'),
                'created_by' => Auth::id(),
            ], $lines);
        } catch (\Throwable $e) {
            Session::flash('error', $e->getMessage());
            $this->back();
            return;
        }

        Audit::log('reservation.create', 'reservation', $reservation['id']);
        // Fire booking-confirmation notification (WhatsApp + email) asynchronously.
        (new NotificationManager())->sendReservationConfirmation((int) $reservation['id']);

        Session::flash('success', 'Reservation ' . $reservation['code'] . ' created.');
        $this->redirect('/reservations/' . $reservation['id']);
    }

    public function show($params): void
    {
        $this->authorize('reservations.view');
        $res = $this->reservations->find($params['id']);
        if (!$res) {
            Session::flash('error', 'Reservation not found.');
            $this->redirect('/reservations');
        }
        $guest = (new Guest())->find($res['guest_id']);
        $this->view('reservations/show', [
            'title' => $res['code'],
            'res' => $res,
            'guest' => $guest,
            'rooms' => $this->reservations->rooms((int) $res['id']),
            'availableRooms' => (new Room())->available(
                (int) $res['hotel_id'], $res['check_in'], $res['check_out']
            ),
            'payments' => App::db()->all(
                'SELECT * FROM payments WHERE reservation_id = ? ORDER BY created_at DESC', [$res['id']]
            ),
        ]);
    }

    public function update($params): void
    {
        $this->authorize('reservations.manage');
        $data = [];
        foreach (['check_in','check_out','adults','children','special_requests','status'] as $f) {
            $v = Request::input($f);
            if ($v !== null && $v !== '') $data[$f] = $v;
        }
        $this->reservations->update($params['id'], $data);
        Audit::log('reservation.update', 'reservation', $params['id']);
        Session::flash('success', 'Reservation updated.');
        $this->redirect('/reservations/' . $params['id']);
    }

    public function cancel($params): void
    {
        $this->authorize('reservations.manage');
        $this->reservations->update($params['id'], [
            'status' => 'cancelled',
            'cancelled_at' => date('Y-m-d H:i:s'),
        ]);
        Audit::log('reservation.cancel', 'reservation', $params['id']);
        Session::flash('success', 'Reservation cancelled.');
        $this->redirect('/reservations/' . $params['id']);
    }

    /** Availability timeline calendar. */
    public function calendar(): void
    {
        $this->authorize('reservations.view');
        $hotelId = $this->currentHotelId();
        $start = Request::get('start', date('Y-m-d'));
        $days = 14;
        $dates = [];
        for ($i = 0; $i < $days; $i++) {
            $dates[] = date('Y-m-d', strtotime("$start +$i days"));
        }
        $rooms = (new Room())->withType($hotelId);
        // Map of room_id => [date => reservation code]
        $bookings = App::db()->all(
            "SELECT rr.room_id, res.code, res.check_in, res.check_out
             FROM reservation_rooms rr JOIN reservations res ON res.id = rr.reservation_id
             WHERE res.hotel_id = ? AND res.status IN ('confirmed','checked_in','pending')
               AND res.check_out >= ? AND res.check_in <= ?",
            [$hotelId, $start, end($dates)]
        );
        $this->view('reservations/calendar', [
            'title' => 'Availability Calendar',
            'rooms' => $rooms,
            'dates' => $dates,
            'bookings' => $bookings,
            'start' => $start,
        ]);
    }
}
