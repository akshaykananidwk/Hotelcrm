<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Core\App;
use App\Services\ReportService;

class ReportController extends Controller
{
    private array $available = [
        'occupancy' => 'Occupancy',
        'revenue' => 'Revenue',
        'adr' => 'ADR & RevPAR',
        'daily-sales' => 'Daily Sales',
        'gst' => 'GST Summary',
        'housekeeping' => 'Housekeeping',
        'ota' => 'OTA Performance',
        'source' => 'Booking Source',
        'collection' => 'Payment Collection',
        'staff' => 'Staff Activity',
    ];

    public function index(): void
    {
        $this->authorize('reports.view');
        $this->view('reports/index', [
            'title' => 'Reports',
            'available' => $this->available,
        ]);
    }

    public function show($params): void
    {
        $this->authorize('reports.view');
        $type = $params['type'];
        if (!isset($this->available[$type])) {
            $this->redirect('/reports');
        }
        $hotelId = $this->currentHotelId();
        $from = Request::get('from', date('Y-m-01'));
        $to = Request::get('to', date('Y-m-d'));
        $report = (new ReportService())->generate($type, $hotelId, $from, $to);

        $this->view('reports/show', [
            'title' => $this->available[$type] . ' Report',
            'type' => $type,
            'label' => $this->available[$type],
            'report' => $report,
            'from' => $from,
            'to' => $to,
        ]);
    }

    /** Export report to CSV / Excel / PDF. */
    public function export($params): void
    {
        $this->authorize('reports.view');
        $type = $params['type'];
        if (!isset($this->available[$type])) {
            $this->redirect('/reports');
        }
        $hotelId = $this->currentHotelId();
        $from = Request::get('from', date('Y-m-01'));
        $to = Request::get('to', date('Y-m-d'));
        $format = Request::get('format', 'csv');
        $report = (new ReportService())->generate($type, $hotelId, $from, $to);

        $rows = $report['rows'] ?? [];
        $filename = "{$type}-report-{$from}-to-{$to}";

        if ($format === 'pdf') {
            $pdf = new \App\Services\PdfService();
            $pdf->heading($this->available[$type] . ' Report')->line("$from to $to")->rule();
            foreach ($rows as $r) {
                $pdf->line(implode('  |  ', array_map(fn ($v) => (string) $v, $r)), 9);
            }
            header('Content-Type: application/pdf');
            header('Content-Disposition: attachment; filename="' . $filename . '.pdf"');
            echo $pdf->output();
            return;
        }

        // CSV / Excel (Excel opens CSV natively; we send xls-friendly headers).
        $mime = $format === 'excel' ? 'application/vnd.ms-excel' : 'text/csv';
        $ext = $format === 'excel' ? 'xls' : 'csv';
        header("Content-Type: $mime");
        header('Content-Disposition: attachment; filename="' . $filename . '.' . $ext . '"');
        $out = fopen('php://output', 'w');
        if (!empty($rows)) {
            fputcsv($out, array_keys($rows[0]));
            foreach ($rows as $r) {
                fputcsv($out, $r);
            }
        }
        fclose($out);
    }
}
