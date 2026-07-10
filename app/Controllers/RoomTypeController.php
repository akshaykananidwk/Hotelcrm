<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Session;
use App\Core\Audit;
use App\Models\RoomType;

class RoomTypeController extends Controller
{
    private RoomType $types;

    public function __construct()
    {
        $this->types = new RoomType();
    }

    public function index(): void
    {
        $this->authorize('rooms.manage');
        $hotelId = $this->currentHotelId();
        $this->view('roomtypes/index', [
            'title' => 'Room Types',
            'types' => $this->types->forHotel($hotelId),
        ]);
    }

    public function store(): void
    {
        $this->authorize('rooms.manage');
        $data = $this->validate([
            'name' => 'required|max:120',
            'base_price' => 'required|numeric|min:0',
            'max_adults' => 'required|int|min:1',
            'tax_rate' => 'numeric|min:0|max:100',
        ]);
        $data['hotel_id'] = $this->currentHotelId();
        foreach (['code','description','max_children','status'] as $f) {
            $v = Request::input($f);
            if ($v !== null && $v !== '') $data[$f] = $v;
        }
        $id = $this->types->create($data);
        Audit::log('room_type.create', 'room_type', $id);
        Session::flash('success', 'Room type added.');
        $this->redirect('/room-types');
    }

    public function update($params): void
    {
        $this->authorize('rooms.manage');
        $data = $this->validate(['name' => 'required', 'base_price' => 'required|numeric']);
        foreach (['code','description','max_adults','max_children','tax_rate','status'] as $f) {
            $v = Request::input($f);
            if ($v !== null && $v !== '') $data[$f] = $v;
        }
        $this->types->update($params['id'], $data);
        Audit::log('room_type.update', 'room_type', $params['id']);
        Session::flash('success', 'Room type updated.');
        $this->redirect('/room-types');
    }

    public function destroy($params): void
    {
        $this->authorize('rooms.manage');
        $this->types->delete($params['id']);
        Session::flash('success', 'Room type deleted.');
        $this->redirect('/room-types');
    }
}
