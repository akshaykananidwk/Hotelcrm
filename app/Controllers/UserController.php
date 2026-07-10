<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Session;
use App\Core\Security;
use App\Core\Auth;
use App\Core\Audit;
use App\Models\User;

class UserController extends Controller
{
    private User $users;

    public function __construct()
    {
        $this->users = new User();
    }

    public function index(): void
    {
        $this->authorize('users.manage');
        // Super admin sees all users; hotel admins see their own hotel's staff.
        $hotelId = Auth::hotelId();
        $this->view('users/index', [
            'title' => 'Users & Roles',
            'users' => $this->users->withRole($hotelId),
            'roles' => $this->users->roles(),
        ]);
    }

    public function store(): void
    {
        $this->authorize('users.manage');
        $data = $this->validate([
            'name' => 'required|max:120',
            'email' => 'required|email',
            'password' => 'required|min:8',
            'role_id' => 'required|int',
        ]);
        if ($this->users->findBy('email', $data['email'])) {
            Session::flash('error', 'A user with that email already exists.');
            $this->back();
            return;
        }
        $data['password'] = Security::hash($data['password']);
        $data['hotel_id'] = Auth::hotelId() ?? (Request::input('hotel_id') ?: null);
        $data['status'] = Request::input('status', 'active');
        $id = $this->users->create($data);
        Audit::log('user.create', 'user', $id);
        Session::flash('success', 'User created.');
        $this->redirect('/users');
    }

    public function update($params): void
    {
        $this->authorize('users.manage');
        $data = [
            'name' => Request::input('name'),
            'email' => Request::input('email'),
            'role_id' => Request::input('role_id'),
            'status' => Request::input('status', 'active'),
        ];
        $password = Request::input('password');
        if (!empty($password)) {
            $data['password'] = Security::hash($password);
        }
        $this->users->update($params['id'], $data);
        Audit::log('user.update', 'user', $params['id']);
        Session::flash('success', 'User updated.');
        $this->redirect('/users');
    }

    public function destroy($params): void
    {
        $this->authorize('users.manage');
        if ((int) $params['id'] === Auth::id()) {
            Session::flash('error', 'You cannot delete your own account.');
            $this->back();
            return;
        }
        $this->users->delete($params['id']);
        Audit::log('user.delete', 'user', $params['id']);
        Session::flash('success', 'User deleted.');
        $this->redirect('/users');
    }
}
