<?php
namespace App\Services;

use App\Core\App;
use App\Models\Reservation;
use App\Models\Room;
use App\Models\RoomType;

/**
 * Encapsulates reservation creation so the admin panel, REST API and public
 * booking engine all share identical availability + pricing logic.
 */
class BookingService
{
    private Reservation $reservations;
    private Room $rooms;
    private RoomType $types;

    public function __construct()
    {
        $this->reservations = new Reservation();
        $this->rooms = new Room();
        $this->types = new RoomType();
    }

    public function nights(string $checkIn, string $checkOut): int
    {
        $n = (int) ((strtotime($checkOut) - strtotime($checkIn)) / 86400);
        return max(1, $n);
    }

    /**
     * Create a reservation with one or more rooms.
     *
     * @param array $data    hotel_id, guest_id, check_in, check_out, source, ...
     * @param array $lines   [['room_type_id'=>, 'room_id'=>?, 'rate'=>?], ...]
     * @return array         the created reservation row
     * @throws \RuntimeException on validation / availability failure
     */
    public function create(array $data, array $lines): array
    {
        if (empty($lines)) {
            throw new \RuntimeException('At least one room is required.');
        }
        if (strtotime($data['check_out']) <= strtotime($data['check_in'])) {
            throw new \RuntimeException('Check-out must be after check-in.');
        }
        $nights = $this->nights($data['check_in'], $data['check_out']);
        $db = App::db();
        $db->beginTransaction();
        try {
            $total = 0.0;
            $prepared = [];
            foreach ($lines as $line) {
                $type = $this->types->find($line['room_type_id']);
                if (!$type) {
                    throw new \RuntimeException('Invalid room type.');
                }
                $rate = isset($line['rate']) && $line['rate'] !== '' ? (float) $line['rate'] : (float) $type['base_price'];

                // If a specific room was requested, confirm it is free.
                $roomId = $line['room_id'] ?? null;
                if ($roomId) {
                    $available = $this->rooms->available(
                        (int) $data['hotel_id'], $data['check_in'], $data['check_out'], (int) $type['id']
                    );
                    $freeIds = array_column($available, 'id');
                    if (!in_array((int) $roomId, array_map('intval', $freeIds), true)) {
                        throw new \RuntimeException("Room is not available for the selected dates.");
                    }
                }
                $lineTotal = $rate * $nights;
                $tax = $lineTotal * ((float) $type['tax_rate'] / 100);
                $total += $lineTotal + $tax;
                $prepared[] = [
                    'room_id' => $roomId,
                    'room_type_id' => (int) $type['id'],
                    'rate' => $rate,
                    'nights' => $nights,
                    'tax_rate' => (float) $type['tax_rate'],
                ];
            }

            $code = $this->reservations->generateCode();
            $resId = $this->reservations->create(array_merge($data, [
                'code' => $code,
                'total_amount' => round($total, 2),
                'status' => $data['status'] ?? 'confirmed',
            ]));

            foreach ($prepared as $p) {
                $db->insert('reservation_rooms', array_merge(['reservation_id' => $resId], $p));
            }

            $db->commit();
            return $this->reservations->find($resId);
        } catch (\Throwable $e) {
            $db->rollBack();
            throw $e;
        }
    }
}
