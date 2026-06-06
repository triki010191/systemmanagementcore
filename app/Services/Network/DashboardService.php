<?php

namespace App\Services\Network;

use App\Enums\CoreStatus;
use App\Enums\NetworkNodeType;
use App\Models\Customer;
use App\Models\FiberCable;
use App\Models\FiberCore;
use App\Models\MaintenanceSchedule;
use App\Models\NetworkNode;
use App\Models\Odc;
use App\Models\Odp;
use App\Models\OtdrRecord;
use App\Models\TroubleTicket;
use App\Models\User;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Collection;

class DashboardService
{
    private const HIGH_LOSS_THRESHOLD_DB = 0.35;

    public function metrics(): array
    {
        $totalCores = FiberCore::query()->count();
        $usedCores = FiberCore::query()->where('status', CoreStatus::Used)->count();
        $coreUsagePercent = $totalCores > 0
            ? round(($usedCores / $totalCores) * 100, 1)
            : 0;

        $totalNodes = NetworkNode::query()->count();
        $activeNodes = NetworkNode::query()->where('status', 'active')->count();
        $healthPercent = $totalNodes > 0
            ? round(($activeNodes / $totalNodes) * 100, 1)
            : 100;

        $cableLengthKm = FiberCable::query()->sum('length_meters') / 1000;

        return [
            'total_customers' => Customer::query()->count(),
            'active_customers' => Customer::query()->where('status', 'active')->count(),
            'total_odp' => Odp::query()->count(),
            'total_odc' => Odc::query()->count(),
            'total_cables' => FiberCable::query()->count(),
            'cable_length_km' => round((float) $cableLengthKm, 1),
            'core_usage_percent' => $coreUsagePercent,
            'core_available_percent' => round(100 - $coreUsagePercent, 1),
            'open_tickets' => TroubleTicket::query()->whereIn('status', ['open', 'in_progress'])->count(),
            'network_health_percent' => $healthPercent,
            'fault_nodes' => NetworkNode::query()->where('status', 'fault')->count(),
            'nodes_in_maintenance' => NetworkNode::query()->where('status', 'maintenance')->count(),
            'otdr_readings' => OtdrRecord::query()->count(),
            'otdr_high_loss' => OtdrRecord::query()->where('total_loss_db', '>=', self::HIGH_LOSS_THRESHOLD_DB)->count(),
            'maintenance_due' => MaintenanceSchedule::query()
                ->where('status', 'scheduled')
                ->where('scheduled_at', '<=', now()->addDays(7))
                ->count(),
            'maintenance_in_progress' => MaintenanceSchedule::query()->where('status', 'in_progress')->count(),
            'maintenance_overdue' => MaintenanceSchedule::query()
                ->where('status', 'scheduled')
                ->where('scheduled_at', '<', now())
                ->count(),
        ];
    }

    /** @return Collection<int, DatabaseNotification> */
    public function recentNotifications(User $user, int $limit = 5): Collection
    {
        return $user->notifications()
            ->latest()
            ->limit($limit)
            ->get();
    }

    public function unreadNotificationCount(User $user): int
    {
        return $user->unreadNotifications()->count();
    }

    /** @return Collection<int, MaintenanceSchedule> */
    public function maintenanceAlerts(int $limit = 5): Collection
    {
        return MaintenanceSchedule::query()
            ->with(['networkNode', 'assignee'])
            ->where('status', 'scheduled')
            ->where('scheduled_at', '<=', now()->addDay())
            ->orderBy('scheduled_at')
            ->limit($limit)
            ->get();
    }

    public function recentIncidents(int $limit = 5): Collection
    {
        return TroubleTicket::query()
            ->with(['networkNode', 'customer'])
            ->latest()
            ->limit($limit)
            ->get();
    }

    public function recentOtdrReadings(int $limit = 5): Collection
    {
        return OtdrRecord::query()
            ->with(['cableCore.cable', 'technician'])
            ->latest('measured_at')
            ->limit($limit)
            ->get();
    }

    public function upcomingMaintenance(int $limit = 5): Collection
    {
        return MaintenanceSchedule::query()
            ->with(['networkNode', 'assignee'])
            ->whereIn('status', ['scheduled', 'in_progress'])
            ->orderBy('scheduled_at')
            ->limit($limit)
            ->get();
    }

    public function nodeCountsByType(): array
    {
        $counts = [];

        foreach (NetworkNodeType::cases() as $type) {
            $counts[$type->value] = NetworkNode::query()->where('type', $type)->count();
        }

        return $counts;
    }

    /** @return list<array{label: string, value: int, color: string}> */
    public function coreStatusChart(): array
    {
        $colors = [
            CoreStatus::Available->value => '#22c55e',
            CoreStatus::Used->value => '#3b82f6',
            CoreStatus::Reserved->value => '#f59e0b',
            CoreStatus::Broken->value => '#ef4444',
            CoreStatus::Maintenance->value => '#8b5cf6',
        ];

        $counts = FiberCore::query()
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $segments = [];

        foreach (CoreStatus::cases() as $status) {
            $segments[] = [
                'label' => $status->label(),
                'value' => (int) ($counts[$status->value] ?? 0),
                'color' => $colors[$status->value],
            ];
        }

        return $segments;
    }

    /** @return list<array{label: string, value: int, color: string}> */
    public function ticketStatusChart(): array
    {
        $map = [
            'open' => ['label' => __('hfnms.ticket_status_open'), 'color' => '#ef4444'],
            'in_progress' => ['label' => __('hfnms.ticket_status_in_progress'), 'color' => '#f59e0b'],
            'resolved' => ['label' => __('hfnms.ticket_status_resolved'), 'color' => '#22c55e'],
            'closed' => ['label' => __('hfnms.ticket_status_closed'), 'color' => '#64748b'],
        ];

        $counts = TroubleTicket::query()
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $items = [];

        foreach ($map as $key => $meta) {
            $items[] = [
                'label' => $meta['label'],
                'value' => (int) ($counts[$key] ?? 0),
                'color' => $meta['color'],
            ];
        }

        return $items;
    }

    /** @return list<array{label: string, value: int, color: string}> */
    public function nodeDistributionChart(): array
    {
        $palette = ['#3b82f6', '#6366f1', '#8b5cf6', '#06b6d4', '#14b8a6', '#f97316', '#ec4899'];
        $items = [];
        $i = 0;

        foreach (NetworkNodeType::cases() as $type) {
            $count = NetworkNode::query()->where('type', $type)->count();

            if ($count <= 0) {
                continue;
            }

            $items[] = [
                'label' => strtoupper($type->value),
                'value' => $count,
                'color' => $palette[$i % count($palette)],
            ];
            $i++;
        }

        return $items;
    }

    /** @return list<array{label: string, value: int}> */
    public function ticketTrend(int $days = 7): array
    {
        $start = now()->subDays($days - 1)->startOfDay();
        $counts = TroubleTicket::query()
            ->where('created_at', '>=', $start)
            ->selectRaw('DATE(created_at) as day, count(*) as total')
            ->groupBy('day')
            ->orderBy('day')
            ->pluck('total', 'day');

        $points = [];

        for ($i = 0; $i < $days; $i++) {
            $date = $start->copy()->addDays($i);
            $key = $date->format('Y-m-d');
            $points[] = [
                'label' => $date->format('d/m'),
                'value' => (int) ($counts[$key] ?? 0),
            ];
        }

        return $points;
    }

    public function chartTotals(array $segments): int
    {
        return (int) collect($segments)->sum('value');
    }
}
