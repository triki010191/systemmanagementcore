<?php

namespace App\Services\Network;

use App\Models\TroubleTicket;
use App\Services\Audit\AuditLogService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class TroubleTicketService
{
    public function __construct(
        private readonly AuditLogService $auditLog,
    ) {}

    public function paginate(?string $status = null, int $perPage = 15): LengthAwarePaginator
    {
        return TroubleTicket::query()
            ->with(['networkNode', 'customer', 'assignee'])
            ->when($status, fn ($q) => $q->where('status', $status))
            ->latest()
            ->paginate($perPage)
            ->withQueryString();
    }

    public function find(int $id): TroubleTicket
    {
        return TroubleTicket::query()
            ->with(['networkNode', 'customer.networkNode', 'assignee'])
            ->findOrFail($id);
    }

    /** @param array<string, mixed> $data */
    public function create(array $data): TroubleTicket
    {
        return DB::transaction(function () use ($data) {
            $data['ticket_number'] = $this->nextTicketNumber();
            $data = $this->applyStatusTimestamps($data);
            $ticket = TroubleTicket::query()->create($data);
            $this->auditLog->log('create', 'trouble_ticket', $ticket->id, null, $this->auditSnapshot($ticket));

            return $ticket;
        });
    }

    /** @param array<string, mixed> $data */
    public function update(TroubleTicket $ticket, array $data): TroubleTicket
    {
        $old = $this->auditSnapshot($ticket);
        $data = $this->applyStatusTimestamps($data, $ticket);
        $ticket->update($data);
        $ticket = $ticket->fresh(['networkNode', 'customer', 'assignee']);
        $this->auditLog->log('update', 'trouble_ticket', $ticket->id, $old, $this->auditSnapshot($ticket));

        return $ticket;
    }

    public function delete(TroubleTicket $ticket): void
    {
        DB::transaction(function () use ($ticket) {
            $old = $this->auditSnapshot($ticket);
            $id = $ticket->id;
            $ticket->delete();
            $this->auditLog->log('delete', 'trouble_ticket', $id, $old, null);
        });
    }

    private function nextTicketNumber(): string
    {
        $year = now()->format('Y');
        $prefix = "INC-{$year}-";
        $last = TroubleTicket::query()
            ->where('ticket_number', 'like', "{$prefix}%")
            ->orderByDesc('ticket_number')
            ->value('ticket_number');

        $sequence = $last ? ((int) substr($last, -4)) + 1 : 1;

        return $prefix.str_pad((string) $sequence, 4, '0', STR_PAD_LEFT);
    }

    /** @param array<string, mixed> $data */
    private function applyStatusTimestamps(array $data, ?TroubleTicket $existing = null): array
    {
        $status = $data['status'] ?? $existing?->status;

        if (in_array($status, ['resolved', 'closed'], true)) {
            $data['resolved_at'] = $data['resolved_at'] ?? now();
        } elseif ($existing && in_array($existing->status, ['resolved', 'closed'], true) && ! in_array($status, ['resolved', 'closed'], true)) {
            $data['resolved_at'] = null;
        }

        return $data;
    }

    /** @return array<string, mixed> */
    private function auditSnapshot(TroubleTicket $ticket): array
    {
        return [
            'ticket_number' => $ticket->ticket_number,
            'title' => $ticket->title,
            'status' => $ticket->status,
            'priority' => $ticket->priority,
        ];
    }
}
