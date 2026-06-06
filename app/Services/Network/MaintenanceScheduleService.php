<?php

namespace App\Services\Network;

use App\Models\MaintenanceSchedule;
use App\Services\Audit\AuditLogService;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class MaintenanceScheduleService
{
    public function __construct(
        private readonly AuditLogService $auditLog,
    ) {}

    public function paginate(?string $status = null, int $perPage = 15): LengthAwarePaginator
    {
        return MaintenanceSchedule::query()
            ->with(['networkNode', 'assignee'])
            ->when($status, fn ($q) => $q->where('status', $status))
            ->orderBy('scheduled_at')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function find(int $id): MaintenanceSchedule
    {
        return MaintenanceSchedule::query()
            ->with(['networkNode', 'assignee'])
            ->findOrFail($id);
    }

    /** @return Collection<int, MaintenanceSchedule> */
    public function eventsForMonth(int $year, int $month): Collection
    {
        $start = Carbon::create($year, $month, 1)->startOfMonth();
        $end = $start->copy()->endOfMonth();

        return MaintenanceSchedule::query()
            ->with(['networkNode', 'assignee'])
            ->whereBetween('scheduled_at', [$start, $end])
            ->orderBy('scheduled_at')
            ->get();
    }

    /** @param array<string, mixed> $data */
    public function create(array $data): MaintenanceSchedule
    {
        return DB::transaction(function () use ($data) {
            $data = $this->applyStatusTimestamps($data);
            $schedule = MaintenanceSchedule::query()->create($data);
            $this->auditLog->log('create', 'maintenance_schedule', $schedule->id, null, $this->auditSnapshot($schedule));

            return $schedule;
        });
    }

    /** @param array<string, mixed> $data */
    public function update(MaintenanceSchedule $schedule, array $data): MaintenanceSchedule
    {
        $old = $this->auditSnapshot($schedule);
        $data = $this->applyStatusTimestamps($data, $schedule);

        if (array_key_exists('scheduled_at', $data)) {
            $newScheduled = \Carbon\Carbon::parse($data['scheduled_at']);

            if (! $schedule->scheduled_at?->equalTo($newScheduled)) {
                $data['due_notified_at'] = null;
            }
        }

        $schedule->update($data);
        $schedule = $schedule->fresh(['networkNode', 'assignee']);
        $this->auditLog->log('update', 'maintenance_schedule', $schedule->id, $old, $this->auditSnapshot($schedule));

        return $schedule;
    }

    public function delete(MaintenanceSchedule $schedule): void
    {
        DB::transaction(function () use ($schedule) {
            $old = $this->auditSnapshot($schedule);
            $id = $schedule->id;
            $schedule->delete();
            $this->auditLog->log('delete', 'maintenance_schedule', $id, $old, null);
        });
    }

    /** @param array<string, mixed> $data */
    private function applyStatusTimestamps(array $data, ?MaintenanceSchedule $existing = null): array
    {
        $status = $data['status'] ?? $existing?->status;

        if ($status === 'completed') {
            $data['completed_at'] = $data['completed_at'] ?? now();
        } elseif ($existing && $existing->status === 'completed' && $status !== 'completed') {
            $data['completed_at'] = null;
        }

        return $data;
    }

    /** @return array<string, mixed> */
    private function auditSnapshot(MaintenanceSchedule $schedule): array
    {
        return [
            'title' => $schedule->title,
            'status' => $schedule->status,
            'schedule_type' => $schedule->schedule_type,
            'scheduled_at' => $schedule->scheduled_at?->toIso8601String(),
            'network_node_id' => $schedule->network_node_id,
        ];
    }
}
