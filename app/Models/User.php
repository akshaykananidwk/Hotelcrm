<?php
namespace App\Models;

use App\Core\Model;

class User extends Model
{
    protected string $table = 'users';
    protected array $fillable = [
        'hotel_id', 'role_id', 'name', 'email', 'phone',
        'password', 'avatar', 'status',
    ];

    public function withRole(?int $hotelId = null): array
    {
        $where = $hotelId ? 'u.hotel_id = ?' : '1';
        $params = $hotelId ? [$hotelId] : [];
        return $this->db()->all(
            "SELECT u.*, r.name AS role_name, r.slug AS role_slug, h.name AS hotel_name
             FROM users u JOIN roles r ON r.id = u.role_id
             LEFT JOIN hotels h ON h.id = u.hotel_id
             WHERE $where ORDER BY u.name ASC",
            $params
        );
    }

    public function roles(): array
    {
        return $this->db()->all('SELECT * FROM roles ORDER BY id ASC');
    }
}
