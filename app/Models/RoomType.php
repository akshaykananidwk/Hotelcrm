<?php
namespace App\Models;

use App\Core\Model;

class RoomType extends Model
{
    protected string $table = 'room_types';
    protected array $fillable = [
        'hotel_id', 'name', 'code', 'description', 'base_price',
        'max_adults', 'max_children', 'tax_rate', 'amenities', 'status',
    ];

    public function forHotel(int $hotelId): array
    {
        return $this->all('hotel_id = ?', [$hotelId], 'name ASC');
    }
}
