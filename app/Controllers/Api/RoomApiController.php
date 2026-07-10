<?php
namespace App\Controllers\Api;

use App\Core\Request;
use App\Models\Room;

class RoomApiController extends ApiController
{
    public function index(): void
    {
        $rooms = (new Room())->withType($this->hotelId());
        $this->success($rooms);
    }

    public function availability(): void
    {
        $checkIn = Request::get('check_in');
        $checkOut = Request::get('check_out');
        if (!$checkIn || !$checkOut) {
            $this->error('check_in and check_out are required.', 422);
            return;
        }
        $rooms = (new Room())->available(
            $this->hotelId(), $checkIn, $checkOut, Request::get('room_type_id') ? (int) Request::get('room_type_id') : null
        );
        $this->success(['available' => $rooms, 'count' => count($rooms)]);
    }
}
