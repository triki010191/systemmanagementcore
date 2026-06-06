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
        $user = $request->user();

        $coreStatusChart = $this->dashboardService->coreStatusChart();

        return view('dashboard', [
            'metrics' => $this->dashboardService->metrics(),
            'incidents' => $this->dashboardService->recentIncidents(),
            'otdrReadings' => $this->dashboardService->recentOtdrReadings(),
            'upcomingMaintenance' => $this->dashboardService->upcomingMaintenance(),
            'nodeCounts' => $this->dashboardService->nodeCountsByType(),
            'recentNotifications' => $this->dashboardService->recentNotifications($user),
            'maintenanceAlerts' => $this->dashboardService->maintenanceAlerts(),
            'unreadNotificationCount' => $this->dashboardService->unreadNotificationCount($user),
            'coreStatusChart' => $coreStatusChart,
            'coreStatusTotal' => $this->dashboardService->chartTotals($coreStatusChart),
            'ticketStatusChart' => $this->dashboardService->ticketStatusChart(),
            'nodeDistributionChart' => $this->dashboardService->nodeDistributionChart(),
            'ticketTrend' => $this->dashboardService->ticketTrend(),
        ]);
    }
}
