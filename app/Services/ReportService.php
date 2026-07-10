<?php
namespace App\Services;

use App\Core\App;

/**
 * Report generation. Each report returns:
 *   ['summary' => [...kpis], 'rows' => [...tabular], 'chart' => [...optional]]
 */
class ReportService
{
    public function generate(string $type, int $hotelId, string $from, string $to): array
    {
        return match ($type) {
            'occupancy'    => $this->occupancy($hotelId, $from, $to),
            'revenue'      => $this->revenue($hotelId, $from, $to),
            'adr'          => $this->adr($hotelId, $from, $to),
            'daily-sales'  => $this->dailySales($hotelId, $from, $to),
            'gst'          => $this->gst($hotelId, $from, $to),
            'housekeeping' => $this->housekeeping($hotelId),
            'ota'          => $this->ota($hotelId, $from, $to),
            'source'       => $this->source($hotelId, $from, $to),
            'collection'   => $this->collection($hotelId, $from, $to),
            'staff'        => $this->staff($hotelId, $from, $to),
            default        => ['summary' => [], 'rows' => []],
        };
    }

    private function db()
    {
        return App::db();
    }

    private function occupancy(int $hotelId, string $from, string $to): array
    {
        $totalRooms = (int) $this->db()->scalar('SELECT COUNT(*) FROM rooms WHERE hotel_id = ?', [$hotelId]);
        $rows = $this->db()->all(
            "SELECT DATE(check_in) AS date, COUNT(*) AS bookings
             FROM reservations WHERE hotel_id = ? AND status IN ('checked_in','checked_out','confirmed')
               AND check_in BETWEEN ? AND ? GROUP BY DATE(check_in) ORDER BY date",
            [$hotelId, $from, $to]
        );
        foreach ($rows as &$r) {
            $r['occupancy_%'] = $totalRooms > 0 ? round($r['bookings'] / $totalRooms * 100, 1) : 0;
        }
        $avg = $rows ? round(array_sum(array_column($rows, 'occupancy_%')) / count($rows), 1) : 0;
        return ['summary' => ['Total Rooms' => $totalRooms, 'Avg Occupancy' => $avg . '%'], 'rows' => $rows];
    }

    private function revenue(int $hotelId, string $from, string $to): array
    {
        $rows = $this->db()->all(
            "SELECT DATE(created_at) AS date,
                    SUM(CASE WHEN type='payment' THEN amount ELSE 0 END) AS collected,
                    SUM(CASE WHEN type='refund' THEN amount ELSE 0 END) AS refunded
             FROM payments WHERE hotel_id = ? AND status='success' AND DATE(created_at) BETWEEN ? AND ?
             GROUP BY DATE(created_at) ORDER BY date",
            [$hotelId, $from, $to]
        );
        $total = array_sum(array_column($rows, 'collected'));
        return ['summary' => ['Total Collected' => '₹' . number_format((float) $total, 2)], 'rows' => $rows];
    }

    private function adr(int $hotelId, string $from, string $to): array
    {
        $totalRooms = (int) $this->db()->scalar('SELECT COUNT(*) FROM rooms WHERE hotel_id = ?', [$hotelId]);
        $roomRevenue = (float) $this->db()->scalar(
            "SELECT COALESCE(SUM(rr.rate * rr.nights),0) FROM reservation_rooms rr
             JOIN reservations r ON r.id = rr.reservation_id
             WHERE r.hotel_id = ? AND r.check_in BETWEEN ? AND ? AND r.status IN ('checked_in','checked_out','confirmed')",
            [$hotelId, $from, $to]
        );
        $roomsSold = (int) $this->db()->scalar(
            "SELECT COALESCE(SUM(rr.nights),0) FROM reservation_rooms rr
             JOIN reservations r ON r.id = rr.reservation_id
             WHERE r.hotel_id = ? AND r.check_in BETWEEN ? AND ? AND r.status IN ('checked_in','checked_out','confirmed')",
            [$hotelId, $from, $to]
        );
        $nights = max(1, (int) ((strtotime($to) - strtotime($from)) / 86400) + 1);
        $adr = $roomsSold > 0 ? $roomRevenue / $roomsSold : 0;
        $revpar = $totalRooms > 0 ? $roomRevenue / ($totalRooms * $nights) : 0;
        return [
            'summary' => [
                'Room Revenue' => '₹' . number_format($roomRevenue, 2),
                'Room Nights Sold' => $roomsSold,
                'ADR' => '₹' . number_format($adr, 2),
                'RevPAR' => '₹' . number_format($revpar, 2),
            ],
            'rows' => [
                ['metric' => 'ADR (Average Daily Rate)', 'value' => round($adr, 2)],
                ['metric' => 'RevPAR (Revenue Per Available Room)', 'value' => round($revpar, 2)],
            ],
        ];
    }

    private function dailySales(int $hotelId, string $from, string $to): array
    {
        $rows = $this->db()->all(
            "SELECT DATE(i.issued_at) AS date, COUNT(*) AS invoices, SUM(i.total) AS billed, SUM(i.paid) AS collected
             FROM invoices i WHERE i.hotel_id = ? AND DATE(i.issued_at) BETWEEN ? AND ?
             GROUP BY DATE(i.issued_at) ORDER BY date",
            [$hotelId, $from, $to]
        );
        return ['summary' => ['Days' => count($rows)], 'rows' => $rows];
    }

    private function gst(int $hotelId, string $from, string $to): array
    {
        $rows = $this->db()->all(
            "SELECT ii.tax_rate AS gst_rate, SUM(ii.unit_price*ii.quantity) AS taxable, SUM(ii.tax_amount) AS tax
             FROM invoice_items ii JOIN invoices i ON i.id = ii.invoice_id
             WHERE i.hotel_id = ? AND DATE(i.issued_at) BETWEEN ? AND ?
             GROUP BY ii.tax_rate ORDER BY ii.tax_rate",
            [$hotelId, $from, $to]
        );
        $totalTax = array_sum(array_column($rows, 'tax'));
        return ['summary' => ['Total GST' => '₹' . number_format((float) $totalTax, 2)], 'rows' => $rows];
    }

    private function housekeeping(int $hotelId): array
    {
        $rows = $this->db()->all(
            'SELECT housekeeping AS status, COUNT(*) AS rooms FROM rooms WHERE hotel_id = ? GROUP BY housekeeping',
            [$hotelId]
        );
        return ['summary' => [], 'rows' => $rows];
    }

    private function ota(int $hotelId, string $from, string $to): array
    {
        $rows = $this->db()->all(
            "SELECT COALESCE(channel,'direct') AS channel, COUNT(*) AS bookings, SUM(total_amount) AS revenue
             FROM reservations WHERE hotel_id = ? AND check_in BETWEEN ? AND ?
             GROUP BY channel ORDER BY revenue DESC",
            [$hotelId, $from, $to]
        );
        return ['summary' => ['Channels' => count($rows)], 'rows' => $rows];
    }

    private function source(int $hotelId, string $from, string $to): array
    {
        $rows = $this->db()->all(
            "SELECT source, COUNT(*) AS bookings, SUM(total_amount) AS revenue
             FROM reservations WHERE hotel_id = ? AND check_in BETWEEN ? AND ?
             GROUP BY source ORDER BY bookings DESC",
            [$hotelId, $from, $to]
        );
        return ['summary' => [], 'rows' => $rows];
    }

    private function collection(int $hotelId, string $from, string $to): array
    {
        $rows = $this->db()->all(
            "SELECT method, COUNT(*) AS count, SUM(amount) AS total
             FROM payments WHERE hotel_id = ? AND type='payment' AND status='success'
               AND DATE(created_at) BETWEEN ? AND ? GROUP BY method ORDER BY total DESC",
            [$hotelId, $from, $to]
        );
        return ['summary' => ['Total' => '₹' . number_format((float) array_sum(array_column($rows, 'total')), 2)], 'rows' => $rows];
    }

    private function staff(int $hotelId, string $from, string $to): array
    {
        $rows = $this->db()->all(
            "SELECT u.name AS staff, COUNT(*) AS actions
             FROM audit_logs a JOIN users u ON u.id = a.user_id
             WHERE a.hotel_id = ? AND DATE(a.created_at) BETWEEN ? AND ?
             GROUP BY u.id ORDER BY actions DESC",
            [$hotelId, $from, $to]
        );
        return ['summary' => [], 'rows' => $rows];
    }
}
