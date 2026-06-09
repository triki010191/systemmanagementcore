<?php

namespace App\Http\Controllers\Network;

use App\Enums\CoreStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Network\UpdateCoreRequest;
use App\Models\FiberCore;
use App\Services\Network\CoreManagementService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use RuntimeException;

class CoreManagementController extends Controller
{
    public function __construct(
        private readonly CoreManagementService $coreService,
    ) {}

    public function index(Request $request): View
    {
        $this->authorize('core.manage');

        $cores = $this->coreService->paginate(
            $request->integer('cable_id') ?: null,
            $request->string('status')->toString() ?: null,
        );

        return view('network.cores.index', [
            'cores' => $cores,
            'cables' => $this->coreService->cableOptions(),
            'statusCounts' => $this->coreService->statusCounts(),
            'statuses' => CoreStatus::cases(),
            'filters' => [
                'cable_id' => $request->integer('cable_id') ?: '',
                'status' => $request->string('status')->toString(),
            ],
        ]);
    }

    public function edit(FiberCore $core): View
    {
        $this->authorize('core.manage');

        $core = $this->coreService->find($core->id);

        return view('network.cores.form', [
            'core' => $core,
            'nodes' => $this->coreService->nodeOptions(),
            'statuses' => CoreStatus::cases(),
            'canDelete' => ! $this->coreService->isReferenced($core) && $core->status !== CoreStatus::Used,
        ]);
    }

    public function update(UpdateCoreRequest $request, FiberCore $core): RedirectResponse
    {
        try {
            $this->coreService->update($core, $request->validated());
        } catch (RuntimeException $e) {
            return back()->withInput()->withErrors(['form' => $e->getMessage()]);
        }

        return redirect()
            ->route('cores.index', ['cable_id' => $core->cable_id])
            ->with('success', __('hfnms.core_updated'));
    }

    public function destroy(FiberCore $core): RedirectResponse
    {
        $this->authorize('core.manage');

        $cableId = $core->cable_id;

        try {
            $this->coreService->delete($core);
        } catch (RuntimeException $e) {
            return back()->withErrors(['form' => $e->getMessage()]);
        }

        return redirect()
            ->route('cores.index', ['cable_id' => $cableId])
            ->with('success', __('hfnms.core_deleted'));
    }
}
