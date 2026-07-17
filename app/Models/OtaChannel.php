<?php
namespace App\Models;

use App\Core\Model;

class OtaChannel extends Model
{
    protected string $table = 'ota_channels';
    protected array $fillable = [
        'hotel_id', 'channel', 'display_name', 'credentials',
        'connection_mode', 'automation_config',
        'is_enabled', 'last_sync_at', 'last_status',
    ];

    public function forHotel(int $hotelId): array
    {
        return $this->all('hotel_id = ?', [$hotelId], 'display_name ASC');
    }

    public function byChannel(int $hotelId, string $channel): ?array
    {
        return $this->db()->first(
            'SELECT * FROM ota_channels WHERE hotel_id = ? AND channel = ?',
            [$hotelId, $channel]
        );
    }
}
