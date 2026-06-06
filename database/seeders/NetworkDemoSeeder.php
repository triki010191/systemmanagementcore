<?php

namespace Database\Seeders;

use App\Enums\CoreStatus;
use App\Enums\LinkDirection;
use App\Enums\NetworkLinkType;
use App\Enums\NetworkNodeStatus;
use App\Enums\NetworkNodeType;
use App\Enums\PortStatus;
use App\Enums\SplitterPortDirection;
use App\Enums\SplitterRatio;
use App\Models\AuditLog;
use App\Models\Customer;
use App\Models\CustomerConnection;
use App\Models\FiberCable;
use App\Models\FiberCore;
use App\Models\NetworkLink;
use App\Models\NetworkNode;
use App\Models\MaintenanceSchedule;
use App\Models\Odc;
use App\Models\Odp;
use App\Models\Olt;
use App\Models\Otb;
use App\Models\OtdrRecord;
use App\Models\Pop;
use App\Models\Splitter;
use App\Models\SplitterPort;
use App\Models\TroubleTicket;
use App\Models\User;
use App\Services\Network\CableProvisioningService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class NetworkDemoSeeder extends Seeder
{
    public function run(): void
    {
        if (Customer::query()->where('code', 'HNT000001')->exists()) {
            $this->seedOpsDemoDataIfMissing();
            $this->seedAuditDemoIfMissing();

            return;
        }

        DB::transaction(function () {
            $popNode = $this->createNode(NetworkNodeType::Pop, 'POP-JKT-01', 'POP Jakarta Pusat', -6.1751, 106.8650);
            $oltNode = $this->createNode(NetworkNodeType::Olt, 'OLT-JKT-01', 'OLT ZTE C600', -6.1752, 106.8652, $popNode->id);
            $otbNode = $this->createNode(NetworkNodeType::Otb, 'OTB-001', 'OTB Tray Utama', -6.1755, 106.8655, $oltNode->id);
            $odcNode = $this->createNode(NetworkNodeType::Odc, 'ODC-001', 'ODC Cluster Menteng', -6.1760, 106.8660, $otbNode->id);
            $splitterNode = $this->createNode(NetworkNodeType::Splitter, 'SPL-ODC-001', 'Splitter 1:8', -6.1761, 106.8661, $odcNode->id);
            $odpNode = $this->createNode(NetworkNodeType::Odp, 'ODP-001', 'ODP Jalan Sudirman', -6.1765, 106.8665, $splitterNode->id);
            $customerNode = $this->createNode(NetworkNodeType::Customer, 'HNT000001', 'Budi Santoso', -6.1770, 106.8670, $odpNode->id);

            $pop = Pop::query()->create([
                'network_node_id' => $popNode->id,
                'upstream_provider' => 'IX Indonesia',
                'router_model' => 'MikroTik CCR2004',
                'monitoring_enabled' => true,
            ]);

            $olt = Olt::query()->create([
                'network_node_id' => $oltNode->id,
                'pop_id' => $pop->id,
                'brand' => 'ZTE',
                'model' => 'C600',
                'slot_count' => 8,
                'pon_port_count' => 16,
                'ip_address' => '10.10.1.1',
            ]);

            $otb = Otb::query()->create([
                'network_node_id' => $otbNode->id,
                'code' => 'OTB-001',
                'olt_id' => $olt->id,
                'tray_count' => 4,
                'capacity_cores' => 96,
            ]);

            $odc = Odc::query()->create([
                'network_node_id' => $odcNode->id,
                'code' => 'ODC-001',
                'otb_id' => $otb->id,
                'port_capacity' => 96,
                'port_used' => 12,
                'split_ratio_default' => '1:8',
            ]);

            $splitter = Splitter::query()->create([
                'network_node_id' => $splitterNode->id,
                'odc_id' => $odc->id,
                'ratio' => SplitterRatio::Ratio1x8,
                'input_port_count' => 1,
                'output_port_count' => 8,
                'brand' => 'FiberHome',
            ]);

            $odp = Odp::query()->create([
                'network_node_id' => $odpNode->id,
                'code' => 'ODP-001',
                'odc_id' => $odc->id,
                'port_capacity' => 16,
                'port_used' => 3,
            ]);

            $customer = Customer::query()->create([
                'network_node_id' => $customerNode->id,
                'code' => 'HNT000001',
                'name' => 'Budi Santoso',
                'phone' => '081234567890',
                'email' => 'budi.santoso@email.com',
                'address' => 'Jl. Sudirman No. 10, Jakarta',
                'service_type' => 'home',
                'vlan_tag' => 100,
                'ip_address' => '192.168.100.10',
                'onu_serial' => 'ONU-ZTE-F601-8829',
                'rx_power_dbm' => -18.40,
                'tx_power_dbm' => 2.10,
                'status' => 'active',
                'registered_at' => now()->subMonths(6)->toDateString(),
            ]);

            $inputPort = SplitterPort::query()->create([
                'splitter_id' => $splitter->id,
                'port_number' => 1,
                'direction' => SplitterPortDirection::Input,
                'status' => PortStatus::Active,
                'label' => 'IN-1',
            ]);

            $outputPort = SplitterPort::query()->create([
                'splitter_id' => $splitter->id,
                'port_number' => 3,
                'direction' => SplitterPortDirection::Output,
                'status' => PortStatus::Active,
                'label' => 'OUT-3',
            ]);

            for ($i = 1; $i <= 8; $i++) {
                if ($i === 3) {
                    continue;
                }

                SplitterPort::query()->create([
                    'splitter_id' => $splitter->id,
                    'port_number' => $i,
                    'direction' => SplitterPortDirection::Output,
                    'status' => PortStatus::Empty,
                    'label' => "OUT-{$i}",
                ]);
            }

            $cable = FiberCable::query()->create([
                'code' => 'CBL-96-DEMO-TRUNK-01',
                'name' => 'Kabel Backbone Demo OTB-ODC',
                'manufacturer' => 'Furukawa',
                'fiber_type' => 'G.652.D',
                'cable_type' => 'backbone',
                'core_count' => 96,
                'tube_count' => 8,
                'length_meters' => 1200,
                'installed_at' => now()->subYear()->toDateString(),
                'start_node_id' => $otbNode->id,
                'end_node_id' => $odcNode->id,
                'status' => 'active',
                'route_geometry' => [
                    ['lat' => -6.1755, 'lng' => 106.8655],
                    ['lat' => -6.1757, 'lng' => 106.8657],
                    ['lat' => -6.1758, 'lng' => 106.8658],
                    ['lat' => -6.1760, 'lng' => 106.8660],
                ],
                'metadata' => ['map_color' => '#D32F2F'],
            ]);

            app(CableProvisioningService::class)->provision($cable);

            $usedCore = FiberCore::query()
                ->where('cable_id', $cable->id)
                ->where('core_number', 12)
                ->first();

            $usedCore?->update([
                'status' => CoreStatus::Used,
                'source_node_id' => $otbNode->id,
                'target_node_id' => $odcNode->id,
                'loss_db' => 0.35,
            ]);

            $outputPort->update(['connected_core_id' => $usedCore?->id]);

            CustomerConnection::query()->create([
                'customer_id' => $customer->id,
                'odp_id' => $odp->id,
                'cable_core_id' => $usedCore?->id,
                'splitter_port_id' => $outputPort->id,
                'odp_port_number' => 3,
                'drop_length_m' => 85.50,
                'connected_at' => now()->subMonths(6)->toDateString(),
            ]);

            $this->createLink($oltNode, $otbNode, NetworkLinkType::PatchCord, $usedCore);
            $this->createLink($otbNode, $odcNode, NetworkLinkType::FiberCable, $usedCore, $cable);
            $this->createLink($odcNode, $splitterNode, NetworkLinkType::SplitterConnection, $usedCore, null, $inputPort);
            $this->createLink($splitterNode, $odpNode, NetworkLinkType::SplitterConnection, null, null, null, $outputPort);
            $this->createLink($odpNode, $customerNode, NetworkLinkType::Drop, null);

            TroubleTicket::query()->create([
                'ticket_number' => 'INC-2026-0001',
                'network_node_id' => $odpNode->id,
                'customer_id' => $customer->id,
                'priority' => 'medium',
                'status' => 'open',
                'title' => 'RX power rendah di ODP-001',
                'description' => 'Beberapa pelanggan melaporkan sinyal lemah di area Sudirman.',
            ]);

            TroubleTicket::query()->create([
                'ticket_number' => 'INC-2026-0002',
                'network_node_id' => $odcNode->id,
                'priority' => 'high',
                'status' => 'in_progress',
                'title' => 'Core #12 attenuation tinggi',
                'description' => 'OTDR menunjukkan loss 0.35 dB di segmen OTB-ODC.',
            ]);

            $this->seedOpsDemoData($usedCore, $otbNode, $odcNode);
            $this->seedAuditDemoIfMissing();
        });
    }

    private function seedOpsDemoDataIfMissing(): void
    {
        $usedCore = FiberCore::query()
            ->whereHas('cable', fn ($q) => $q->where('code', 'CBL-96-DEMO-TRUNK-01'))
            ->where('core_number', 12)
            ->first();

        $otbNode = NetworkNode::query()->where('code', 'OTB-001')->first();
        $odcNode = NetworkNode::query()->where('code', 'ODC-001')->first();

        if ($usedCore && $otbNode && $odcNode) {
            $this->seedOpsDemoData($usedCore, $otbNode, $odcNode);
        }

        $this->attachDemoTraceIfMissing();
    }

    private function attachDemoTraceIfMissing(): void
    {
        $record = OtdrRecord::query()->first();

        if (! $record || $record->trace_file_path) {
            return;
        }

        $tracePath = 'otdr-traces/'.$record->id.'/CBL-96-CORE12-DEMO.sor';
        Storage::disk('local')->put($tracePath, implode("\n", [
            'DEMO OTDR TRACE FILE',
            'Cable: CBL-96-DEMO-TRUNK-01',
            'Core: 12',
            'Total Loss: 0.35 dB',
            'Fiber Length: 1.200 km',
            'Event @ 0.850 km Loss 0.35 dB',
        ]));
        $record->update(['trace_file_path' => $tracePath]);
    }

    private function seedAuditDemoIfMissing(): void
    {
        if (AuditLog::query()->exists()) {
            return;
        }

        $userId = User::query()->value('id');
        $now = now();

        AuditLog::query()->create([
            'user_id' => $userId,
            'action' => 'create',
            'entity_type' => 'otdr_record',
            'entity_id' => OtdrRecord::query()->value('id'),
            'old_values' => null,
            'new_values' => ['total_loss_db' => 0.35, 'has_trace_file' => true],
            'ip_address' => '127.0.0.1',
            'created_at' => $now->copy()->subDays(3),
        ]);

        AuditLog::query()->create([
            'user_id' => $userId,
            'action' => 'update',
            'entity_type' => 'trouble_ticket',
            'entity_id' => TroubleTicket::query()->where('ticket_number', 'INC-2026-0002')->value('id'),
            'old_values' => ['status' => 'open'],
            'new_values' => ['status' => 'in_progress'],
            'ip_address' => '127.0.0.1',
            'created_at' => $now->copy()->subDay(),
        ]);

        AuditLog::query()->create([
            'user_id' => $userId,
            'action' => 'create',
            'entity_type' => 'maintenance_schedule',
            'entity_id' => MaintenanceSchedule::query()->value('id'),
            'old_values' => null,
            'new_values' => ['title' => 'Pembersihan tray OTB-001', 'status' => 'scheduled'],
            'ip_address' => '127.0.0.1',
            'created_at' => $now->copy()->subHours(6),
        ]);

        $popNodeId = NetworkNode::query()->where('type', 'pop')->value('id');
        if ($popNodeId) {
            AuditLog::query()->create([
                'user_id' => $userId,
                'action' => 'create',
                'entity_type' => 'network_asset',
                'entity_id' => $popNodeId,
                'old_values' => null,
                'new_values' => ['asset_type' => 'pop', 'code' => 'POP-001', 'name' => 'POP Utama', 'status' => 'active'],
                'ip_address' => '127.0.0.1',
                'created_at' => $now->copy()->subDays(7),
            ]);
        }
    }

    private function seedOpsDemoData(FiberCore $usedCore, NetworkNode $otbNode, NetworkNode $odcNode): void
    {
        if (OtdrRecord::query()->exists() && MaintenanceSchedule::query()->exists()) {
            return;
        }

        $userId = User::query()->value('id');

        if (! OtdrRecord::query()->exists()) {
            $record = OtdrRecord::query()->create([
                'cable_core_id' => $usedCore->id,
                'total_loss_db' => 0.35,
                'distance_km' => 1.200,
                'fault_distance_km' => 0.850,
                'event_points' => [
                    ['index' => 1, 'distance_km' => 0.85, 'loss_db' => 0.35],
                ],
                'notes' => 'OTDR menunjukkan loss tinggi di segmen OTB-ODC dekat manhole MH-03.',
                'measured_by' => $userId,
                'measured_at' => now()->subDays(3),
            ]);

            $tracePath = 'otdr-traces/'.$record->id.'/CBL-96-CORE12-DEMO.sor';
            Storage::disk('local')->put($tracePath, implode("\n", [
                'DEMO OTDR TRACE FILE',
                'Total Loss: 0.35 dB',
                'Fiber Length: 1.200 km',
                'Event @ 0.850 km Loss 0.35 dB',
            ]));
            $record->update(['trace_file_path' => $tracePath]);
        }

        if (! MaintenanceSchedule::query()->exists()) {
            MaintenanceSchedule::query()->create([
                'title' => 'Pembersihan tray OTB-001',
                'description' => 'Inspeksi preventif dan pembersihan debu di OTB tray utama.',
                'network_node_id' => $otbNode->id,
                'schedule_type' => 'preventive',
                'status' => 'scheduled',
                'priority' => 'medium',
                'assigned_to' => $userId,
                'scheduled_at' => now()->addHours(12),
            ]);

            MaintenanceSchedule::query()->create([
                'title' => 'Perbaikan core #12 attenuation',
                'description' => 'Follow-up OTDR loss 0.35 dB — splice ulang di jarak 850m.',
                'network_node_id' => $odcNode->id,
                'schedule_type' => 'corrective',
                'status' => 'in_progress',
                'priority' => 'high',
                'assigned_to' => $userId,
                'scheduled_at' => now()->subDay(),
            ]);
        }
    }

    private function createNode(
        NetworkNodeType $type,
        string $code,
        string $name,
        float $lat,
        float $lng,
        ?int $parentId = null,
    ): NetworkNode {
        return NetworkNode::query()->create([
            'uuid' => (string) Str::uuid(),
            'type' => $type,
            'code' => $code,
            'name' => $name,
            'latitude' => $lat,
            'longitude' => $lng,
            'parent_id' => $parentId,
            'status' => NetworkNodeStatus::Active,
            'address' => 'Jakarta Pusat, DKI Jakarta',
        ]);
    }

    private function createLink(
        NetworkNode $source,
        NetworkNode $target,
        NetworkLinkType $linkType,
        ?FiberCore $core = null,
        ?FiberCable $cable = null,
        ?SplitterPort $sourcePort = null,
        ?SplitterPort $targetPort = null,
    ): void {
        NetworkLink::query()->create([
            'source_node_id' => $source->id,
            'target_node_id' => $target->id,
            'link_type' => $linkType,
            'direction' => LinkDirection::Downstream,
            'cable_id' => $cable?->id,
            'fiber_core_id' => $core?->id,
            'source_port_id' => $sourcePort?->id,
            'target_port_id' => $targetPort?->id,
            'core_number' => $core?->core_number,
            'tube_number' => $core?->tube_number,
            'length_meters' => $cable?->length_meters ?? 85.50,
            'loss_db' => $core?->loss_db,
            'status' => 'active',
        ]);
    }
}
