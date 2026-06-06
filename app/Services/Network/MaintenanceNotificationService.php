<?php

namespace App\Services\Network;

use App\Models\MaintenanceSchedule;
use App\Models\User;
use App\Notifications\MaintenanceDueNotification;
use App\Services\Cms\SettingsService;
use Illuminate\Support\Collection;

class MaintenanceNotificationService
{
    public function __construct(
        private readonly SettingsService $settingsService,
    ) {}

    public function notifyDueSchedules(): int
    {
        if (! $this->settingsService->featureEnabled('maintenance_notify_enabled')) {
            return 0;
        }
        $schedules = MaintenanceSchedule::query()
            ->with(['assignee', 'networkNode'])
            ->where('status', 'scheduled')
            ->where('scheduled_at', '<=', now()->addDay())
            ->whereNull('due_notified_at')
            ->get();

        $count = 0;

        foreach ($schedules as $schedule) {
            $recipients = $this->recipientsFor($schedule);

            if ($recipients->isEmpty()) {
                continue;
            }

            $isOverdue = $schedule->scheduled_at->isPast();

            foreach ($recipients as $user) {
                $user->notify(new MaintenanceDueNotification($schedule, $isOverdue));
            }

            $schedule->update(['due_notified_at' => now()]);
            $count++;
        }

        return $count;
    }

    /** @return Collection<int, User> */
    private function recipientsFor(MaintenanceSchedule $schedule): Collection
    {
        if ($schedule->assignee) {
            return collect([$schedule->assignee]);
        }

        return User::query()
            ->permission('maintenance.manage')
            ->get();
    }
}
