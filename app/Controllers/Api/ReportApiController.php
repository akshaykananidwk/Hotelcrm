<?php
namespace App\Controllers\Api;

use App\Core\Request;
use App\Services\ReportService;

class ReportApiController extends ApiController
{
    public function show($params): void
    {
        $type = $params['type'];
        $from = Request::get('from', date('Y-m-01'));
        $to = Request::get('to', date('Y-m-d'));
        $report = (new ReportService())->generate($type, $this->hotelId(), $from, $to);
        $this->success($report);
    }
}
