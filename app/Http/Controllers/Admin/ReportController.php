<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\ReportService;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Garage rental reports: per month, per day, per garage type, per tenant.
 */
class ReportController extends Controller
{
    private const VIEWS = ['monthly', 'daily', 'type', 'tenant'];

    public function __construct(private ReportService $reportService) {}

    public function index(Request $request): View
    {
        $view = in_array($request->query('view'), self::VIEWS, true) ? $request->query('view') : 'monthly';
        $year = (int) $request->query('year', today()->year);
        $month = min(max((int) $request->query('month', today()->month), 1), 12);

        $rows = match ($view) {
            'monthly' => $this->reportService->monthlyInYear($year),
            'daily' => $this->reportService->dailyInMonth($year, $month),
            'type' => $this->reportService->byGarageType(),
            'tenant' => $this->reportService->byTenant(),
        };

        return view('admin.reports.index', compact('view', 'year', 'month', 'rows'));
    }
}
