<?php

namespace App\Services\Network;

use App\DTO\SorParseResult;
use App\Models\FiberCore;
use App\Models\OtdrRecord;
use App\Services\Audit\AuditLogService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class OtdrRecordService
{
    private const TRACE_DISK_PATH = 'otdr-traces';

    public function __construct(
        private readonly AuditLogService $auditLog,
        private readonly SorParserService $sorParser,
    ) {}

    public function parseTraceFile(UploadedFile $file): SorParseResult
    {
        return $this->sorParser->parse($file->getRealPath());
    }

    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        return OtdrRecord::query()
            ->with(['cableCore.cable', 'technician'])
            ->latest('measured_at')
            ->latest('id')
            ->paginate($perPage);
    }

    public function find(int $id): OtdrRecord
    {
        return OtdrRecord::query()
            ->with(['cableCore.cable', 'cableCore.sourceNode', 'cableCore.targetNode', 'technician'])
            ->findOrFail($id);
    }

    /** @param array<string, mixed> $data */
    public function create(array $data, ?UploadedFile $traceFile = null): OtdrRecord
    {
        return DB::transaction(function () use ($data, $traceFile) {
            $record = OtdrRecord::query()->create($data);

            if ($traceFile) {
                $this->storeAndParseTrace($record, $traceFile);
            }

            $record = $record->fresh();
            $this->syncCoreLoss($record);
            $this->auditLog->log('create', 'otdr_record', $record->id, null, $this->auditSnapshot($record));

            return $record->load(['cableCore.cable', 'technician']);
        });
    }

    /** @param array<string, mixed> $data */
    public function update(OtdrRecord $record, array $data, ?UploadedFile $traceFile = null): OtdrRecord
    {
        return DB::transaction(function () use ($record, $data, $traceFile) {
            $old = $this->auditSnapshot($record);

            if ($traceFile) {
                $this->deleteTraceFile($record);
                $this->storeAndParseTrace($record, $traceFile, mergeIntoExisting: true);
                unset($data['trace_file_path']);
            }

            $record->update($data);
            $record = $record->fresh();
            $this->syncCoreLoss($record);

            $this->auditLog->log('update', 'otdr_record', $record->id, $old, $this->auditSnapshot($record));

            return $record->load(['cableCore.cable', 'technician']);
        });
    }

    public function delete(OtdrRecord $record): void
    {
        DB::transaction(function () use ($record) {
            $old = $this->auditSnapshot($record);
            $this->deleteTraceFile($record);
            $id = $record->id;
            $record->delete();
            $this->auditLog->log('delete', 'otdr_record', $id, $old, null);
        });
    }

    public function traceDownloadPath(OtdrRecord $record): ?string
    {
        if (! $record->trace_file_path) {
            return null;
        }

        $path = Storage::disk('local')->path($record->trace_file_path);

        return file_exists($path) ? $path : null;
    }

    public function traceFileName(OtdrRecord $record): ?string
    {
        return $record->trace_file_path ? basename($record->trace_file_path) : null;
    }

    /** @return \Illuminate\Support\Collection<int, FiberCore> */
    public function coreOptions()
    {
        return FiberCore::query()
            ->with('cable')
            ->orderBy('cable_id')
            ->orderBy('core_number')
            ->limit(500)
            ->get();
    }

    private function storeAndParseTrace(OtdrRecord $record, UploadedFile $file, bool $mergeIntoExisting = false): void
    {
        $relativePath = $this->storeTraceFile($record, $file);
        $parsed = $this->sorParser->parse(Storage::disk('local')->path($relativePath));

        $updates = ['trace_file_path' => $relativePath];
        $updates = array_merge($updates, $this->metricsFromParse($parsed, $record, $mergeIntoExisting));

        $record->update($updates);
    }

    /** @return array<string, mixed> */
    private function metricsFromParse(SorParseResult $parsed, OtdrRecord $record, bool $onlyFillEmpty): array
    {
        if (! $parsed->success) {
            return [];
        }

        $updates = [];

        if ($parsed->totalLossDb !== null && (! $onlyFillEmpty || $record->total_loss_db === null)) {
            $updates['total_loss_db'] = $parsed->totalLossDb;
        }

        if ($parsed->distanceKm !== null && (! $onlyFillEmpty || $record->distance_km === null)) {
            $updates['distance_km'] = $parsed->distanceKm;
        }

        if ($parsed->faultDistanceKm !== null && (! $onlyFillEmpty || $record->fault_distance_km === null)) {
            $updates['fault_distance_km'] = $parsed->faultDistanceKm;
        }

        if ($parsed->eventPoints !== [] && (! $onlyFillEmpty || empty($record->event_points))) {
            $updates['event_points'] = $parsed->eventPoints;
        }

        return $updates;
    }

    private function storeTraceFile(OtdrRecord $record, UploadedFile $file): string
    {
        $filename = now()->format('YmdHis').'_'.preg_replace('/[^a-zA-Z0-9._-]/', '_', $file->getClientOriginalName());

        return $file->storeAs(
            self::TRACE_DISK_PATH.'/'.$record->id,
            $filename,
            'local',
        );
    }

    private function deleteTraceFile(OtdrRecord $record): void
    {
        if ($record->trace_file_path && Storage::disk('local')->exists($record->trace_file_path)) {
            Storage::disk('local')->delete($record->trace_file_path);
        }
    }

    private function syncCoreLoss(OtdrRecord $record): void
    {
        if ($record->total_loss_db === null) {
            return;
        }

        FiberCore::query()
            ->whereKey($record->cable_core_id)
            ->update(['loss_db' => $record->total_loss_db]);
    }

    /** @return array<string, mixed> */
    private function auditSnapshot(OtdrRecord $record): array
    {
        return [
            'cable_core_id' => $record->cable_core_id,
            'total_loss_db' => $record->total_loss_db,
            'fault_distance_km' => $record->fault_distance_km,
            'has_trace_file' => (bool) $record->trace_file_path,
        ];
    }
}
