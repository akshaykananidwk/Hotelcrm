<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Session;
use App\Core\Audit;
use App\Core\App;
use App\Models\Room;

class HousekeepingController extends Controller
{
    public function index(): void
    {
        $this->authorize('housekeeping.manage');
        $hotelId = $this->currentHotelId();
        $rooms = (new Room())->withType($hotelId);
        $this->view('housekeeping/index', [
            'title' => 'Housekeeping',
            'rooms' => $rooms,
            'counts' => (new Room())->countHousekeeping($hotelId),
            'staff' => App::db()->all(
                "SELECT u.id, u.name FROM users u JOIN roles r ON r.id = u.role_id
                 WHERE u.hotel_id = ? AND r.slug = 'housekeeping'", [$hotelId]
            ),
            'tasks' => App::db()->all(
                "SELECT t.*, rm.number AS room_number, u.name AS staff_name
                 FROM housekeeping_tasks t
                 JOIN rooms rm ON rm.id = t.room_id
                 LEFT JOIN users u ON u.id = t.assigned_to
                 WHERE t.hotel_id = ? AND t.status != 'completed'
                 ORDER BY t.created_at DESC", [$hotelId]
            ),
        ]);
    }

    public function updateStatus($params): void
    {
        $this->authorize('housekeeping.manage');
        $status = Request::input('housekeeping');
        $roomStatus = $status === 'maintenance' ? 'maintenance' : null;
        $data = ['housekeeping' => $status];
        if ($roomStatus) {
            $data['status'] = 'maintenance';
        } elseif ($status === 'clean' || $status === 'inspected') {
            // Only free the room if it isn't currently occupied.
            $room = (new Room())->find($params['id']);
            if ($room && $room['status'] === 'maintenance') {
                $data['status'] = 'available';
            }
        }
        (new Room())->update($params['id'], $data);
        Audit::log('housekeeping.status', 'room', $params['id'], ['housekeeping' => $status]);
        Session::flash('success', 'Housekeeping status updated.');
        $this->back();
    }

    public function assign(): void
    {
        $this->authorize('housekeeping.manage');
        $hotelId = $this->currentHotelId();
        App::db()->insert('housekeeping_tasks', [
            'hotel_id' => $hotelId,
            'room_id' => (int) Request::input('room_id'),
            'assigned_to' => Request::input('assigned_to') ?: null,
            'type' => Request::input('type', 'cleaning'),
            'priority' => Request::input('priority', 'normal'),
            'notes' => Request::input('notes'),
            'created_at' => date('Y-m-d H:i:s'),
        ]);
        Audit::log('housekeeping.assign', 'room', Request::input('room_id'));
        Session::flash('success', 'Task assigned.');
        $this->back();
    }
}
