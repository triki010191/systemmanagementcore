<?php

namespace App\Http\Controllers\Network;

use App\Http\Controllers\Controller;
use App\Http\Requests\Network\StoreOtdrRecordRequest;
use App\Http\Requests\Network\UpdateOtdrRecordRequest;
use App\Models\OtdrRecord;
use App\Models\User;
use App\Services\Network\OtdrRecordService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class OtdrRecordController extends Controller
{
    public function __construct(
        private readonly OtdrRecordService $otdrService,
    ) {}

    public function index(): View
    {
        $records = $this->otdrService->paginate();

        return view('network.otdr-records.index', compact('records'));
    }

    public function create(Request $request): View
    {
        return view('network.otdr-records.form', [
            'record' => null,
            'cores' => $this->otdrService->coreOptions(),
            'technicians' => $this->technicianOptions(),
            'preselectedCoreId' => $request->integer('core_id') ?: null,
        ]);
    }

    public function store(StoreOtdrRecordRequest $request): RedirectResponse
    {
        $data = $request->validated();
        unset($data['trace_file']);
        $data['measured_by'] = $data['measured_by'] ?? $request->user()?->id;
        $data['measured_at'] = $data['measured_at'] ?? now();

        $record = $this->otdrService->create($data, $request->file('trace_file'));

        return redirect()
            ->route('otdr-records.show', $record)
            ->with('success', __('hfnms.otdr_created'));
    }

    public function show(OtdrRecord $otdrRecord): View
    {
        $record = $this->otdrService->find($otdrRecord->id);

        return view('network.otdr-records.show', compact('record'));
    }

    public function edit(OtdrRecord $otdrRecord): View
    {
        $record = $this->otdrService->find($otdrRecord->id);

        return view('network.otdr-records.form', [
            'record' => $record,
            'cores' => $this->otdrService->coreOptions(),
            'technicians' => $this->technicianOptions(),
            'preselectedCoreId' => null,
        ]);
    }

    public function update(UpdateOtdrRecordRequest $request, OtdrRecord $otdrRecord): RedirectResponse
    {
        $data = $request->validated();
        unset($data['trace_file']);

        $record = $this->otdrService->update($otdrRecord, $data, $request->file('trace_file'));

        return redirect()
            ->route('otdr-records.show', $record)
            ->with('success', __('hfnms.otdr_updated'));
    }

    public function destroy(OtdrRecord $otdrRecord): RedirectResponse
    {
        $this->otdrService->delete($otdrRecord);

        return redirect()
            ->route('otdr-records.index')
            ->with('success', __('hfnms.otdr_deleted'));
    }

    public function downloadTrace(OtdrRecord $otdrRecord): BinaryFileResponse
    {
        $path = $this->otdrService->traceDownloadPath($otdrRecord);
        abort_if($path === null, 404);

        return response()->download($path, $this->otdrService->traceFileName($otdrRecord));
    }

    public function parseSor(Request $request): JsonResponse
    {
        $request->validate([
            'trace_file' => ['required', 'file', 'extensions:sor', 'max:20480'],
        ]);

        $result = $this->otdrService->parseTraceFile($request->file('trace_file'));

        return response()->json($result->toArray());
    }

    /** @return \Illuminate\Support\Collection<int, User> */
    private function technicianOptions()
    {
        return User::query()->orderBy('name')->get(['id', 'name', 'email']);
    }
}
