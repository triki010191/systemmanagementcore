<?php

namespace App\Http\Controllers\Network;

use App\Enums\NetworkNodeType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Network\UpdateOdcDistributionRequest;
use App\Models\Odc;
use App\Services\Network\OdcDistributionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class OdcDistributionController extends Controller
{
    public function __construct(
        private readonly OdcDistributionService $distributionService,
    ) {}

    public function options(Request $request, int $odc): JsonResponse
    {
        $record = Odc::query()->with('networkNode')->findOrFail($odc);

        if ($request->filled('cable_id')) {
            return response()->json([
                'cores' => $this->distributionService->coreOptionsForCable((int) $request->query('cable_id')),
            ]);
        }

        if ($request->filled('target_type')) {
            return response()->json([
                'targets' => $this->distributionService->targetOptions($record, (string) $request->query('target_type')),
            ]);
        }

        return response()->json($this->distributionService->formPayload($record));
    }

    public function update(UpdateOdcDistributionRequest $request, int $odc): RedirectResponse
    {
        $record = Odc::query()->findOrFail($odc);

        try {
            $this->distributionService->sync($record, $request->validated());
        } catch (\InvalidArgumentException $e) {
            return back()->withInput()->withErrors(['distribution' => $e->getMessage()]);
        }

        return redirect()
            ->route('network.assets.edit', [NetworkNodeType::Odc->routeSlug(), $record->id])
            ->with('success', __('hfnms.odc_dist_saved'));
    }
}
