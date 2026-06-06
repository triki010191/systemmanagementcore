<?php

namespace App\Notifications;

use App\Models\MaintenanceSchedule;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class MaintenanceDueNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly MaintenanceSchedule $schedule,
        private readonly bool $isOverdue = false,
    ) {}

    /** @return list<string> */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /** @return array<string, mixed> */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'maintenance_due',
            'schedule_id' => $this->schedule->id,
            'title' => $this->schedule->title,
            'scheduled_at' => $this->schedule->scheduled_at->toIso8601String(),
            'is_overdue' => $this->isOverdue,
            'url' => route('maintenance-schedules.show', $this->schedule),
            'message' => $this->isOverdue
                ? __('hfnms.notification_maintenance_overdue', ['title' => $this->schedule->title])
                : __('hfnms.notification_maintenance_due', ['title' => $this->schedule->title]),
        ];
    }
}
