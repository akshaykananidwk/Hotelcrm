<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Session;
use App\Core\Audit;
use App\Core\App;
use App\Models\Reservation;
use App\Models\Guest;

class FrontOfficeController extends Controller
{
    private Reservation $reservations;

    public function __construct()
    {
        $this->reservations = new Reservation();
    }

    public function index(): void
    {
        $this->authorize('frontoffice.manage');
        $hotelId = $this->currentHotelId();
        $today = date('Y-m-d');
        $this->view('frontoffice/index', [
            'title' => 'Front Office',
            'arrivals' => $this->reservations->arrivalsToday($hotelId, $today),
            'departures' => $this->reservations->departuresToday($hotelId, $today),
            'inHouse' => $this->reservations->inHouse($hotelId),
        ]);
    }

    public function checkin($params): void
    {
        $this->authorize('frontoffice.manage');
        $res = $this->reservations->find($params['id']);
        if (!$res || $res['status'] === 'checked_in') {
            Session::flash('error', 'Reservation cannot be checked in.');
            $this->back();
            return;
        }
        $db = App::db();
        $db->beginTransaction();
        try {
            $this->reservations->update($res['id'], [
                'status' => 'checked_in',
                'checked_in_at' => date('Y-m-d H:i:s'),
            ]);
            // Mark assigned rooms occupied.
            foreach ($this->reservations->rooms((int) $res['id']) as $rr) {
                if ($rr['room_id']) {
                    $db->update('rooms', ['status' => 'occupied'], 'id = :id', ['id' => $rr['room_id']]);
                }
            }
            $db->commit();
        } catch (\Throwable $e) {
            $db->rollBack();
            Session::flash('error', 'Check-in failed: ' . $e->getMessage());
            $this->back();
            return;
        }
        Audit::log('frontoffice.checkin', 'reservation', $res['id']);
        Session::flash('success', 'Guest checked in.');
        $this->back();
    }

    public function checkout($params): void
    {
        $this->authorize('frontoffice.manage');
        $res = $this->reservations->find($params['id']);
        if (!$res || $res['status'] !== 'checked_in') {
            Session::flash('error', 'Reservation is not checked in.');
            $this->back();
            return;
        }
        $db = App::db();
        $db->beginTransaction();
        try {
            $this->reservations->update($res['id'], [
                'status' => 'checked_out',
                'checked_out_at' => date('Y-m-d H:i:s'),
            ]);
            foreach ($this->reservations->rooms((int) $res['id']) as $rr) {
                if ($rr['room_id']) {
                    // Free the room and flag it dirty for housekeeping.
                    $db->update('rooms', ['status' => 'available', 'housekeeping' => 'dirty'], 'id = :id', ['id' => $rr['room_id']]);
                }
            }
            // Award loyalty points (1 point per 100 spent).
            $points = (int) floor((float) $res['total_amount'] / 100);
            if ($points > 0) {
                (new Guest())->addLoyalty((int) $res['guest_id'], $points);
            }
            $db->commit();
        } catch (\Throwable $e) {
            $db->rollBack();
            Session::flash('error', 'Check-out failed: ' . $e->getMessage());
            $this->back();
            return;
        }
        Audit::log('frontoffice.checkout', 'reservation', $res['id']);
        Session::flash('success', 'Guest checked out.');
        $this->back();
    }

    public function roomChange($params): void
    {
        $this->authorize('frontoffice.manage');
        $reservationRoomId = (int) Request::input('reservation_room_id');
        $newRoomId = (int) Request::input('room_id');
        $db = App::db();

        $rr = $db->first('SELECT * FROM reservation_rooms WHERE id = ? AND reservation_id = ?', [$reservationRoomId, $params['id']]);
        if (!$rr) {
            Session::flash('error', 'Invalid room assignment.');
            $this->back();
            return;
        }
        $db->beginTransaction();
        try {
            if ($rr['room_id']) {
                $db->update('rooms', ['status' => 'available', 'housekeeping' => 'dirty'], 'id = :id', ['id' => $rr['room_id']]);
            }
            $db->update('reservation_rooms', ['room_id' => $newRoomId], 'id = :id', ['id' => $reservationRoomId]);
            $db->update('rooms', ['status' => 'occupied'], 'id = :id', ['id' => $newRoomId]);
            $db->commit();
        } catch (\Throwable $e) {
            $db->rollBack();
            Session::flash('error', 'Room change failed.');
            $this->back();
            return;
        }
        Audit::log('frontoffice.room_change', 'reservation', $params['id'], ['to' => $newRoomId]);
        Session::flash('success', 'Room changed.');
        $this->back();
    }
}
