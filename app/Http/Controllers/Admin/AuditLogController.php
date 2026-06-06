<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Audit\AuditLogService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AuditLogController extends Controller
{
    public function __construct(
        private readonly AuditLogService $auditLogService,
    ) {}

    public function index(Request $request): View
    {
        $logs = $this->auditLogService->paginate(
            $request->string('entity_type')->toString() ?: null,
            $request->string('action')->toString() ?: null,
            $request->integer('user_id') ?: null,
        );

        return view('admin.audit-logs.index', [
            'logs' => $logs,
            'entityTypes' => $this->auditLogService->entityTypes(),
            'actions' => $this->auditLogService->actions(),
            'users' => User::query()->orderBy('name')->get(['id', 'name']),
            'filters' => [
                'entity_type' => $request->string('entity_type')->toString(),
                'action' => $request->string('action')->toString(),
                'user_id' => $request->integer('user_id') ?: '',
            ],
        ]);
    }

    public function show(int $auditLog): View
    {
        $log = $this->auditLogService->find($auditLog);

        return view('admin.audit-logs.show', compact('log'));
    }

    public function export(Request $request): StreamedResponse
    {
        $entityType = $request->string('entity_type')->toString() ?: null;
        $action = $request->string('action')->toString() ?: null;
        $userId = $request->integer('user_id') ?: null;

        $filename = 'audit-logs-'.now()->format('Y-m-d-His').'.csv';

        return Response::streamDownload(function () use ($entityType, $action, $userId) {
            $handle = fopen('php://output', 'w');

            fputcsv($handle, [
                'timestamp',
                'user',
                'action',
                'entity_type',
                'entity_id',
                'ip_address',
                'old_values',
                'new_values',
            ]);

            $this->auditLogService->exportCursor($entityType, $action, $userId)
                ->each(function ($log) use ($handle) {
                    fputcsv($handle, [
                        $log->created_at?->format('Y-m-d H:i:s'),
                        $log->user?->name,
                        $log->action,
                        $log->entity_type,
                        $log->entity_id,
                        $log->ip_address,
                        $log->old_values ? json_encode($log->old_values, JSON_UNESCAPED_UNICODE) : '',
                        $log->new_values ? json_encode($log->new_values, JSON_UNESCAPED_UNICODE) : '',
                    ]);
                });

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
}
