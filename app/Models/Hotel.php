<?php
namespace App\Models;

use App\Core\Model;

class Hotel extends Model
{
    protected string $table = 'hotels';
    protected array $fillable = [
        'parent_id', 'name', 'slug', 'legal_name', 'email', 'phone', 'address',
        'city', 'state', 'country', 'pincode', 'gst_number', 'pan_number',
        'currency', 'currency_symbol', 'timezone', 'logo', 'brand_color',
        'check_in_time', 'check_out_time', 'status',
    ];

    public function active(): array
    {
        return $this->all('status = ?', ['active'], 'name ASC');
    }
}
