<?php
namespace App\Models;

use App\Core\Model;

class Room extends Model
{
    protected string $table = 'rooms';
    protected array $fillable = [
        'hotel_id', 'room_type_id', 'floor_id', 'number',
        'status', 'housekeeping', 'notes',
    ];

    /** Rooms joined with their type + floor for listing. */
    public function withType(int $hotelId): array
    {
        return $this->db()->all(
            'SELECT r.*, rt.name AS type_name, rt.base_price, f.name AS floor_name
             FROM rooms r
             JOIN room_types rt ON rt.id = r.room_type_id
             LEFT JOIN floors f ON f.id = r.floor_id
             WHERE r.hotel_id = ?
             ORDER BY r.number ASC',
            [$hotelId]
        );
    }

    /**
     * Room ids that are free for a date range (not blocked/maintenance and not
     * already assigned to an overlapping active reservation).
     */
    public function available(int $hotelId, string $checkIn, string $checkOut, ?int $roomTypeId = null): array
    {
        $params = [$hotelId, $checkOut, $checkIn];
        $typeSql = '';
        if ($roomTypeId) {
            $typeSql = ' AND r.room_type_id = ?';
            $params[] = $roomTypeId;
        }
        return $this->db()->all(
            "SELECT r.*, rt.name AS type_name, rt.base_price
             FROM rooms r
             JOIN room_types rt ON rt.id = r.room_type_id
             WHERE r.hotel_id = ?
               AND r.status IN ('available','occupied')
               AND r.id NOT IN (
                   SELECT rr.room_id FROM reservation_rooms rr
                   JOIN reservations res ON res.id = rr.reservation_id
                   WHERE rr.room_id IS NOT NULL
                     AND res.status IN ('confirmed','checked_in','pending')
                     AND res.check_in < ? AND res.check_out > ?
               )
               $typeSql
             ORDER BY r.number ASC",
            $params
        );
    }

    public function countByStatus(int $hotelId): array
    {
        $rows = $this->db()->all(
            'SELECT status, COUNT(*) AS c FROM rooms WHERE hotel_id = ? GROUP BY status',
            [$hotelId]
        );
        $out = ['available' => 0, 'occupied' => 0, 'blocked' => 0, 'maintenance' => 0];
        foreach ($rows as $r) {
            $out[$r['status']] = (int) $r['c'];
        }
        return $out;
    }

    public function countHousekeeping(int $hotelId): array
    {
        $rows = $this->db()->all(
            'SELECT housekeeping, COUNT(*) AS c FROM rooms WHERE hotel_id = ? GROUP BY housekeeping',
            [$hotelId]
        );
        $out = ['clean' => 0, 'dirty' => 0, 'inspected' => 0, 'maintenance' => 0];
        foreach ($rows as $r) {
            $out[$r['housekeeping']] = (int) $r['c'];
        }
        return $out;
    }
}
