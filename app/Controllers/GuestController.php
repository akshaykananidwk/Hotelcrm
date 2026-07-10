<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Session;
use App\Core\Security;
use App\Core\Audit;
use App\Models\Guest;

class GuestController extends Controller
{
    private Guest $guests;

    public function __construct()
    {
        $this->guests = new Guest();
    }

    public function index(): void
    {
        $this->authorize('guests.view');
        $hotelId = $this->currentHotelId();
        $q = trim((string) Request::get('q', ''));
        $result = $q !== ''
            ? $this->guests->search($hotelId, $q, $this->page())
            : $this->guests->paginate($this->page(), 20, 'hotel_id = ?', [$hotelId]);

        $this->view('guests/index', [
            'title' => 'Guests / CRM',
            'guests' => $result['data'],
            'pg' => $result,
            'q' => $q,
        ]);
    }

    public function create(): void
    {
        $this->authorize('guests.manage');
        $this->view('guests/form', ['title' => 'Add Guest', 'guest' => null]);
    }

    public function store(): void
    {
        $this->authorize('guests.manage');
        $data = $this->validate([
            'first_name' => 'required|max:80',
            'email' => 'email',
            'phone' => 'required|max:40',
        ]);
        $data['hotel_id'] = $this->currentHotelId();

        // Optional ID document upload.
        if (!empty($_FILES['id_document']['name'])) {
            $data['id_document'] = Security::upload($_FILES['id_document'], 'ids');
        }
        $data = $this->collectGuestFields($data);
        $id = $this->guests->create($data);
        Audit::log('guest.create', 'guest', $id);
        Session::flash('success', 'Guest created.');
        $this->redirect('/guests/' . $id);
    }

    public function show($params): void
    {
        $this->authorize('guests.view');
        $guest = $this->guests->find($params['id']);
        if (!$guest) {
            Session::flash('error', 'Guest not found.');
            $this->redirect('/guests');
        }
        $this->view('guests/show', [
            'title' => $this->guests->fullName($guest),
            'guest' => $guest,
            'history' => $this->guests->history((int) $guest['id']),
        ]);
    }

    public function update($params): void
    {
        $this->authorize('guests.manage');
        $data = $this->validate(['first_name' => 'required', 'phone' => 'required']);
        $data = $this->collectGuestFields($data);
        if (!empty($_FILES['id_document']['name'])) {
            $data['id_document'] = Security::upload($_FILES['id_document'], 'ids');
        }
        $this->guests->update($params['id'], $data);
        Audit::log('guest.update', 'guest', $params['id']);
        Session::flash('success', 'Guest updated.');
        $this->redirect('/guests/' . $params['id']);
    }

    /** Collect the full editable guest field set from the request. */
    private function collectGuestFields(array $data): array
    {
        foreach (['last_name','email','gender','dob','address','city','country','id_type',
                  'id_number','nationality','company','gst_number','preferences','notes'] as $f) {
            $val = Request::input($f);
            if ($val !== null && $val !== '') {
                $data[$f] = $val;
            }
        }
        return $data;
    }
}
