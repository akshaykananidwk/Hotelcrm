<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Session;
use App\Core\Audit;
use App\Core\App;
use App\Models\Room;
use App\Models\RoomType;

class RoomController extends Controller
{
    private Room $rooms;

    public function __construct()
    {
        $this->rooms = new Room();
    }

    public function index(): void
    {
        $this->authorize('rooms.view');
        $hotelId = $this->currentHotelId();
        $this->view('rooms/index', [
            'title' => 'Rooms',
            'rooms' => $this->rooms->withType($hotelId),
            'types' => (new RoomType())->forHotel($hotelId),
            'floors' => App::db()->all('SELECT * FROM floors WHERE hotel_id = ?', [$hotelId]),
            'statusCount' => $this->rooms->countByStatus($hotelId),
        ]);
    }

    public function store(): void
    {
        $this->authorize('rooms.manage');
        $data = $this->validate([
            'number' => 'required|max:20',
            'room_type_id' => 'required|int',
        ]);
        $data['hotel_id'] = $this->currentHotelId();
        foreach (['floor_id','status','housekeeping','notes'] as $f) {
            $v = Request::input($f);
            if ($v !== null && $v !== '') $data[$f] = $v;
        }
        $id = $this->rooms->create($data);
        Audit::log('room.create', 'room', $id);
        Session::flash('success', 'Room added.');
        $this->redirect('/rooms');
    }

    public function update($params): void
    {
        $this->authorize('rooms.manage');
        $data = $this->validate(['number' => 'required', 'room_type_id' => 'required|int']);
        foreach (['floor_id','status','housekeeping','notes'] as $f) {
            $v = Request::input($f);
            if ($v !== null && $v !== '') $data[$f] = $v;
        }
        $this->rooms->update($params['id'], $data);
        Audit::log('room.update', 'room', $params['id']);
        Session::flash('success', 'Room updated.');
        $this->redirect('/rooms');
    }

    public function updateStatus($params): void
    {
        $this->authorize('rooms.manage');
        $status = Request::input('status');
        $this->rooms->update($params['id'], ['status' => $status]);
        Audit::log('room.status', 'room', $params['id'], ['status' => $status]);
        if (Request::wantsJson()) {
            $this->json(['ok' => true]);
            return;
        }
        Session::flash('success', 'Room status updated.');
        $this->back();
    }

    public function destroy($params): void
    {
        $this->authorize('rooms.manage');
        $this->rooms->delete($params['id']);
        Session::flash('success', 'Room deleted.');
        $this->redirect('/rooms');
    }
}
