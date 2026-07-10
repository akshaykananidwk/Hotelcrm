<?php
namespace App\Models;

use App\Core\Model;

/**
 * Key/value settings store, grouped and scoped per hotel. Used for all
 * integration credentials (SMTP, WhatsApp, payment gateways, MikroTik, OTA).
 */
class Setting extends Model
{
    protected string $table = 'settings';

    /** Fetch all key => value pairs for a group. */
    public function group(string $group, ?int $hotelId): array
    {
        $rows = $this->db()->all(
            'SELECT key_name, value FROM settings WHERE group_key = ? AND (hotel_id = ? OR hotel_id IS NULL)',
            [$group, $hotelId]
        );
        $out = [];
        foreach ($rows as $r) {
            $out[$r['key_name']] = $r['value'];
        }
        return $out;
    }

    public function get(string $group, string $key, ?int $hotelId, $default = null)
    {
        $val = $this->db()->scalar(
            'SELECT value FROM settings WHERE group_key = ? AND key_name = ? AND (hotel_id = ? OR hotel_id IS NULL) ORDER BY hotel_id DESC LIMIT 1',
            [$group, $key, $hotelId]
        );
        return $val ?? $default;
    }

    /** Upsert a single setting. */
    public function put(string $group, string $key, $value, ?int $hotelId, bool $secret = false): void
    {
        $this->db()->run(
            'INSERT INTO settings (hotel_id, group_key, key_name, value, is_secret)
             VALUES (?, ?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE value = VALUES(value)',
            [$hotelId, $group, $key, $value, $secret ? 1 : 0]
        );
    }

    /** Bulk save a group's values. */
    public function saveGroup(string $group, array $values, ?int $hotelId, array $secrets = []): void
    {
        foreach ($values as $key => $value) {
            $this->put($group, $key, $value, $hotelId, in_array($key, $secrets, true));
        }
    }
}
