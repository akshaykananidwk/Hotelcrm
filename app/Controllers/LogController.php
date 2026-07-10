<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\App;

class LogController extends Controller
{
    public function audit(): void
    {
        $this->authorize('logs.view');
        $page = $this->page();
        $perPage = 30;
        $offset = ($page - 1) * $perPage;
        $total = (int) App::db()->scalar('SELECT COUNT(*) FROM audit_logs');
        $logs = App::db()->all(
            "SELECT a.*, u.name AS user_name FROM audit_logs a
             LEFT JOIN users u ON u.id = a.user_id
             ORDER BY a.id DESC LIMIT $perPage OFFSET $offset"
        );
        $this->view('logs/index', [
            'title' => 'Audit Logs',
            'logs' => $logs,
            'type' => 'audit',
            'pg' => ['page' => $page, 'pages' => (int) ceil($total / $perPage)],
        ]);
    }

    public function activity(): void
    {
        $this->authorize('logs.view');
        $logs = App::db()->all('SELECT * FROM login_logs ORDER BY id DESC LIMIT 100');
        $this->view('logs/index', [
            'title' => 'Login Activity',
            'logs' => $logs,
            'type' => 'login',
            'pg' => ['page' => 1, 'pages' => 1],
        ]);
    }
}
