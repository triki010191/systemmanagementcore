<?php

namespace App\Console\Commands;

use App\Services\Network\MaintenanceNotificationService;
use Illuminate\Console\Command;

class NotifyDueMaintenanceCommand extends Command
{
    protected $signature = 'maintenance:notify-due';

    protected $description = 'Send database notifications for due maintenance schedules';

    public function handle(MaintenanceNotificationService $service): int
    {
        $count = $service->notifyDueSchedules();

        $this->info("Sent notifications for {$count} maintenance schedule(s).");

        return self::SUCCESS;
    }
}
