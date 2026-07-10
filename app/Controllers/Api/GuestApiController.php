<?php
namespace App\Controllers\Api;

use App\Core\Request;
use App\Models\Guest;

class GuestApiController extends ApiController
{
    public function index(): void
    {
        $q = (string) Request::get('q', '');
        $model = new Guest();
        $result = $q !== ''
            ? $model->search($this->hotelId(), $q, max(1, (int) Request::get('page', 1)))
            : $model->paginate(max(1, (int) Request::get('page', 1)), 20, 'hotel_id = ?', [$this->hotelId()]);
        $this->success($result);
    }

    public function store(): void
    {
        $body = Request::all();
        if (empty($body['first_name']) || empty($body['phone'])) {
            $this->error('first_name and phone are required.', 422);
            return;
        }
        $id = (new Guest())->create([
            'hotel_id' => $this->hotelId(),
            'first_name' => $body['first_name'],
            'last_name' => $body['last_name'] ?? null,
            'email' => $body['email'] ?? null,
            'phone' => $body['phone'],
            'city' => $body['city'] ?? null,
            'country' => $body['country'] ?? null,
        ]);
        $this->success((new Guest())->find($id), 201);
    }
}
