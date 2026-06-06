<?php

namespace App\Services\Audit;

use App\Models\AuditLog;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

class AuditLogService
{
    /** @param array<string, mixed>|null $oldValues @param array<string, mixed>|null $newValues */
    public function log(
        string $action,
        string $entityType,
        ?int $entityId = null,
        ?array $oldValues = null,
        ?array $newValues = null,
    ): AuditLog {
        return AuditLog::query()->create([
            'user_id' => Auth::id(),
            'action' => $action,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'ip_address' => Request::ip(),
            'created_at' => now(),
        ]);
    }

    public function paginate(
        ?string $entityType = null,
        ?string $action = null,
        ?int $userId = null,
        int $perPage = 20,
    ): LengthAwarePaginator {
        return $this->filteredQuery($entityType, $action, $userId)
            ->paginate($perPage)
            ->withQueryString();
    }

    /** @return \Illuminate\Database\Eloquent\Builder<AuditLog> */
    public function filteredQuery(
        ?string $entityType = null,
        ?string $action = null,
        ?int $userId = null,
    ) {
        return AuditLog::query()
            ->with('user')
            ->when($entityType, fn ($q) => $q->where('entity_type', $entityType))
            ->when($action, fn ($q) => $q->where('action', $action))
            ->when($userId, fn ($q) => $q->where('user_id', $userId))
            ->latest('created_at');
    }

    /** @return \Illuminate\Support\LazyCollection<int, AuditLog> */
    public function exportCursor(
        ?string $entityType = null,
        ?string $action = null,
        ?int $userId = null,
    ): \Illuminate\Support\LazyCollection {
        return $this->filteredQuery($entityType, $action, $userId)->cursor();
    }

    public function find(int $id): AuditLog
    {
        return AuditLog::query()
            ->with('user')
            ->findOrFail($id);
    }

    /** @return list<string> */
    public function entityTypes(): array
    {
        return AuditLog::query()
            ->distinct()
            ->orderBy('entity_type')
            ->pluck('entity_type')
            ->all();
    }

    /** @return list<string> */
    public function actions(): array
    {
        return AuditLog::query()
            ->distinct()
            ->orderBy('action')
            ->pluck('action')
            ->all();
    }
}
