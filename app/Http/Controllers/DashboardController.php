<?php

namespace App\Http\Controllers;

use App\Services\Network\DashboardService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(
        private readonly DashboardService $dashboardService,
    ) {}

    public function index(Request $request): View
    {
        $coreStatusChart = $this->dashboardService->coreStatusChart();

        return view('dashboard', [
            'metrics' => $this->dashboardService->metrics(),
            'incidents' => $this->dashboardService->recentIncidents(),
            'upcomingMaintenance' => $this->dashboardService->upcomingMaintenance(),
            'nodeCounts' => $this->dashboardService->nodeCountsByType(),
            'coreStatusChart' => $coreStatusChart,
            'coreStatusTotal' => $this->dashboardService->chartTotals($coreStatusChart),
            'ticketTrend' => $this->dashboardService->ticketTrend(),
        ]);
    }
}
