<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Session;
use App\Core\Audit;
use App\Models\Hotel;

class HotelController extends Controller
{
    private Hotel $hotels;

    public function __construct()
    {
        $this->hotels = new Hotel();
    }

    public function index(): void
    {
        $this->authorize('hotels.manage');
        $this->view('hotels/index', [
            'title' => 'Hotels',
            'hotels' => $this->hotels->all('1', [], 'name ASC'),
        ]);
    }

    public function create(): void
    {
        $this->authorize('hotels.manage');
        $this->view('hotels/form', ['title' => 'Add Hotel', 'hotel' => null]);
    }

    public function store(): void
    {
        $this->authorize('hotels.manage');
        $data = $this->validate([
            'name' => 'required|max:160',
            'email' => 'email',
            'currency' => 'required',
        ]);
        $data = $this->collect($data);
        $data['slug'] = $this->slug($data['name']);
        $id = $this->hotels->create($data);
        Audit::log('hotel.create', 'hotel', $id);
        Session::flash('success', 'Hotel created.');
        $this->redirect('/hotels');
    }

    public function edit($params): void
    {
        $this->authorize('hotels.manage');
        $hotel = $this->hotels->find($params['id']);
        $this->view('hotels/form', ['title' => 'Edit Hotel', 'hotel' => $hotel]);
    }

    public function update($params): void
    {
        $this->authorize('hotels.manage');
        $data = $this->validate(['name' => 'required', 'currency' => 'required']);
        $data = $this->collect($data);
        $this->hotels->update($params['id'], $data);
        Audit::log('hotel.update', 'hotel', $params['id']);
        Session::flash('success', 'Hotel updated.');
        $this->redirect('/hotels');
    }

    public function destroy($params): void
    {
        $this->authorize('hotels.manage');
        $this->hotels->delete($params['id']);
        Audit::log('hotel.delete', 'hotel', $params['id']);
        Session::flash('success', 'Hotel deleted.');
        $this->redirect('/hotels');
    }

    private function collect(array $data): array
    {
        foreach (['legal_name','email','phone','address','city','state','country','pincode',
                  'gst_number','pan_number','currency','currency_symbol','timezone','brand_color',
                  'check_in_time','check_out_time','status'] as $f) {
            $v = Request::input($f);
            if ($v !== null && $v !== '') {
                $data[$f] = $v;
            }
        }
        return $data;
    }

    private function slug(string $name): string
    {
        $slug = strtolower(trim(preg_replace('/[^a-z0-9]+/i', '-', $name), '-'));
        return $slug . '-' . substr(bin2hex(random_bytes(2)), 0, 4);
    }
}
