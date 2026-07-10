<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Auth;
use App\Core\App;
use App\Models\Room;
use App\Models\Reservation;

class DashboardController extends Controller
{
    public function index(): void
    {
        $this->authorize('dashboard.view');
        $hotelId = Auth::hotelId() ?? $this->firstHotelId();
        $today = date('Y-m-d');

        $roomModel = new Room();
        $resModel = new Reservation();
        $db = App::db();

        $roomStatus = $roomModel->countByStatus($hotelId);
        $housekeeping = $roomModel->countHousekeeping($hotelId);
        $totalRooms = array_sum($roomStatus);
        $occupied = $roomStatus['occupied'];
        $occupancy = $totalRooms > 0 ? round($occupied / $totalRooms * 100, 1) : 0;

        // Revenue today + pending payments.
        $revenueToday = (float) $db->scalar(
            "SELECT COALESCE(SUM(amount),0) FROM payments
             WHERE hotel_id = ? AND type='payment' AND status='success' AND DATE(created_at) = ?",
            [$hotelId, $today]
        );
        $pendingPayments = (float) $db->scalar(
            "SELECT COALESCE(SUM(balance),0) FROM invoices WHERE hotel_id = ? AND status IN ('unpaid','partial')",
            [$hotelId]
        );
        $monthRevenue = (float) $db->scalar(
            "SELECT COALESCE(SUM(amount),0) FROM payments
             WHERE hotel_id = ? AND type='payment' AND status='success' AND DATE_FORMAT(created_at,'%Y-%m') = ?",
            [$hotelId, date('Y-m')]
        );

        // 7-day revenue trend for the chart.
        $trend = $db->all(
            "SELECT DATE(created_at) AS d, COALESCE(SUM(amount),0) AS total
             FROM payments
             WHERE hotel_id = ? AND type='payment' AND status='success'
               AND created_at >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
             GROUP BY DATE(created_at) ORDER BY d ASC",
            [$hotelId]
        );

        $this->view('dashboard/index', [
            'title' => 'Dashboard',
            'occupancy' => $occupancy,
            'roomStatus' => $roomStatus,
            'housekeeping' => $housekeeping,
            'totalRooms' => $totalRooms,
            'revenueToday' => $revenueToday,
            'monthRevenue' => $monthRevenue,
            'pendingPayments' => $pendingPayments,
            'arrivals' => $resModel->arrivalsToday($hotelId, $today),
            'departures' => $resModel->departuresToday($hotelId, $today),
            'inHouse' => $resModel->inHouse($hotelId),
            'trend' => $trend,
        ]);
    }

    private function firstHotelId(): int
    {
        return (int) (App::db()->scalar('SELECT id FROM hotels ORDER BY id ASC LIMIT 1') ?? 0);
    }
}
