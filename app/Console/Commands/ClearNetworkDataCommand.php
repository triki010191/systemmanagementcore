<?php

namespace App\Console\Commands;

use App\Models\AssetPhoto;
use App\Models\AuditLog;
use App\Models\CableTube;
use App\Models\CoreJoint;
use App\Models\CustomerConnection;
use App\Models\FiberCable;
use App\Models\FiberCore;
use App\Models\MaintenanceSchedule;
use App\Models\NetworkLink;
use App\Models\NetworkNode;
use App\Models\Odc;
use App\Models\Odp;
use App\Models\Olt;
use App\Models\Otb;
use App\Models\OtdrRecord;
use App\Models\Pop;
use App\Models\TroubleTicket;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ClearNetworkDataCommand extends Command
{
    protected $signature = 'network:clear {--force : Hapus tanpa konfirmasi}';

    protected $description = 'Hapus semua data jaringan (node, kabel, pelanggan, tiket, OTDR, maintenance)';

    public function handle(): int
    {
        if (! $this->option('force') && ! $this->confirm('Hapus SEMUA data jaringan? Tindakan ini tidak bisa dibatalkan.', false)) {
            $this->info('Dibatalkan.');

            return self::SUCCESS;
        }

        $counts = DB::transaction(function () {
            $this->purgeOtdrTraceFiles();

            return [
                'notifications' => DB::table('notifications')->delete(),
                'audit_logs' => AuditLog::query()->delete(),
                'otdr_records' => OtdrRecord::query()->delete(),
                'core_joints' => CoreJoint::query()->delete(),
                'maintenance_schedules' => MaintenanceSchedule::query()->delete(),
                'trouble_tickets' => TroubleTicket::query()->delete(),
                'customer_connections' => CustomerConnection::query()->delete(),
                'network_links' => NetworkLink::query()->forceDelete(),
                'fiber_cores' => FiberCore::query()->delete(),
                'cable_tubes' => CableTube::query()->delete(),
                'fiber_cables' => FiberCable::query()->forceDelete(),
                'asset_photos' => AssetPhoto::query()->delete(),
                'customers' => Customer::query()->delete(),
                'odps' => Odp::query()->delete(),
                'odcs' => Odc::query()->delete(),
                'otbs' => Otb::query()->delete(),
                'olts' => Olt::query()->delete(),
                'pops' => Pop::query()->delete(),
                'network_nodes' => NetworkNode::query()->forceDelete(),
            ];
        });

        foreach ($counts as $label => $count) {
            $this->line(sprintf('  %-24s %d', $label.':', $count));
        }

        $this->newLine();
        $this->info('Semua data jaringan dummy telah dihapus. Silakan input data asli via menu Aset Jaringan.');

        return self::SUCCESS;
    }

    private function purgeOtdrTraceFiles(): void
    {
        if (Storage::disk('local')->exists('otdr-traces')) {
            Storage::disk('local')->deleteDirectory('otdr-traces');
        }
    }
}
