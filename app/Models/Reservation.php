<?php
namespace App\Models;

use App\Core\Model;

class Reservation extends Model
{
    protected string $table = 'reservations';
    protected array $fillable = [
        'hotel_id', 'code', 'guest_id', 'source', 'channel', 'ota_reference',
        'check_in', 'check_out', 'adults', 'children', 'status', 'total_amount',
        'paid_amount', 'special_requests', 'company', 'group_id',
        'checked_in_at', 'checked_out_at', 'cancelled_at', 'created_by',
    ];

    /** Generate a unique human-friendly reservation code. */
    public function generateCode(): string
    {
        return 'RSV-' . date('Y') . '-' . str_pad((string) random_int(1, 999999), 6, '0', STR_PAD_LEFT);
    }

    /** Listing with guest name joined. */
    public function withGuest(int $hotelId, string $where = '1', array $params = [], int $page = 1, int $perPage = 20): array
    {
        $params = array_merge([$hotelId], $params);
        $offset = ($page - 1) * $perPage;
        $total = (int) $this->db()->scalar(
            "SELECT COUNT(*) FROM reservations r WHERE r.hotel_id = ? AND ($where)",
            $params
        );
        $data = $this->db()->all(
            "SELECT r.*, CONCAT(g.first_name,' ',COALESCE(g.last_name,'')) AS guest_name, g.phone AS guest_phone
             FROM reservations r JOIN guests g ON g.id = r.guest_id
             WHERE r.hotel_id = ? AND ($where)
             ORDER BY r.check_in DESC LIMIT $perPage OFFSET $offset",
            $params
        );
        return ['data' => $data, 'total' => $total, 'page' => $page, 'perPage' => $perPage, 'pages' => (int) ceil($total / $perPage)];
    }

    public function rooms(int $reservationId): array
    {
        return $this->db()->all(
            'SELECT rr.*, r.number AS room_number, rt.name AS type_name
             FROM reservation_rooms rr
             JOIN room_types rt ON rt.id = rr.room_type_id
             LEFT JOIN rooms r ON r.id = rr.room_id
             WHERE rr.reservation_id = ?',
            [$reservationId]
        );
    }

    public function arrivalsToday(int $hotelId, string $date): array
    {
        return $this->db()->all(
            "SELECT r.*, CONCAT(g.first_name,' ',COALESCE(g.last_name,'')) AS guest_name
             FROM reservations r JOIN guests g ON g.id = r.guest_id
             WHERE r.hotel_id = ? AND r.check_in = ? AND r.status IN ('confirmed','pending')
             ORDER BY r.created_at DESC",
            [$hotelId, $date]
        );
    }

    public function departuresToday(int $hotelId, string $date): array
    {
        return $this->db()->all(
            "SELECT r.*, CONCAT(g.first_name,' ',COALESCE(g.last_name,'')) AS guest_name
             FROM reservations r JOIN guests g ON g.id = r.guest_id
             WHERE r.hotel_id = ? AND r.check_out = ? AND r.status = 'checked_in'
             ORDER BY r.created_at DESC",
            [$hotelId, $date]
        );
    }

    public function inHouse(int $hotelId): array
    {
        return $this->db()->all(
            "SELECT r.*, CONCAT(g.first_name,' ',COALESCE(g.last_name,'')) AS guest_name
             FROM reservations r JOIN guests g ON g.id = r.guest_id
             WHERE r.hotel_id = ? AND r.status = 'checked_in'
             ORDER BY r.check_out ASC",
            [$hotelId]
        );
    }
}
