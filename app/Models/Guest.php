<?php
namespace App\Models;

use App\Core\Model;

class Guest extends Model
{
    protected string $table = 'guests';
    protected array $fillable = [
        'hotel_id', 'first_name', 'last_name', 'email', 'phone', 'gender', 'dob',
        'address', 'city', 'country', 'id_type', 'id_number', 'id_document',
        'nationality', 'company', 'gst_number', 'preferences', 'notes',
        'loyalty_points', 'loyalty_tier', 'is_blacklisted',
    ];

    public function search(int $hotelId, string $q, int $page = 1, int $perPage = 20): array
    {
        $like = '%' . $q . '%';
        return $this->paginate(
            $page, $perPage,
            'hotel_id = ? AND (first_name LIKE ? OR last_name LIKE ? OR email LIKE ? OR phone LIKE ?)',
            [$hotelId, $like, $like, $like, $like]
        );
    }

    public function fullName(array $guest): string
    {
        return trim(($guest['first_name'] ?? '') . ' ' . ($guest['last_name'] ?? ''));
    }

    /** All reservations for a guest (history). */
    public function history(int $guestId): array
    {
        return $this->db()->all(
            'SELECT * FROM reservations WHERE guest_id = ? ORDER BY check_in DESC',
            [$guestId]
        );
    }

    /** Award loyalty points and bump tier. */
    public function addLoyalty(int $guestId, int $points): void
    {
        $this->db()->run('UPDATE guests SET loyalty_points = loyalty_points + ? WHERE id = ?', [$points, $guestId]);
        $total = (int) $this->db()->scalar('SELECT loyalty_points FROM guests WHERE id = ?', [$guestId]);
        $tier = match (true) {
            $total >= 5000 => 'platinum',
            $total >= 2000 => 'gold',
            $total >= 500  => 'silver',
            default        => 'none',
        };
        $this->db()->run('UPDATE guests SET loyalty_tier = ? WHERE id = ?', [$tier, $guestId]);
    }
}
