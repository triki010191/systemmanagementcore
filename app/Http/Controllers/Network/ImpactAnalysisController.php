<?php

namespace App\Http\Controllers\Network;

use App\Http\Controllers\Controller;
use App\Models\FiberCable;
use App\Models\FiberCore;
use App\Models\NetworkNode;
use App\Services\Network\ImpactAnalysisService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ImpactAnalysisController extends Controller
{
    public function __construct(
        private readonly ImpactAnalysisService $impactService,
    ) {}

    public function index(Request $request): View
    {
        $mode = $request->string('mode')->toString() ?: 'node';
        $nodeCode = $request->string('node_code')->trim()->toString();
        $cableCode = $request->string('cable_code')->trim()->toString();
        $coreNumber = $request->integer('core_number');
        $summary = null;
        $error = null;

        if ($mode === 'node' && $nodeCode !== '') {
            $node = NetworkNode::query()->where('code', $nodeCode)->first();

            if ($node) {
                $summary = $this->impactService->impactSummaryByNode($node->id);
            } else {
                $error = __('hfnms.impact_node_not_found', ['code' => $nodeCode]);
            }
        }

        if ($mode === 'core' && $cableCode !== '' && $coreNumber > 0) {
            $cable = FiberCable::query()->where('code', $cableCode)->first();

            if (! $cable) {
                $error = __('hfnms.impact_cable_not_found', ['code' => $cableCode]);
            } else {
                $core = FiberCore::query()
                    ->where('cable_id', $cable->id)
                    ->where('core_number', $coreNumber)
                    ->first();

                if ($core) {
                    $summary = $this->impactService->impactSummaryByCore($core->id);
                } else {
                    $error = __('hfnms.impact_core_not_found', ['cable' => $cableCode, 'core' => $coreNumber]);
                }
            }
        }

        $cables = FiberCable::query()->orderBy('code')->limit(100)->get(['id', 'code', 'name']);

        return view('network.impact-analysis.index', [
            'mode' => $mode,
            'nodeCode' => $nodeCode,
            'cableCode' => $cableCode,
            'coreNumber' => $coreNumber ?: '',
            'summary' => $summary,
            'error' => $error,
            'cables' => $cables,
        ]);
    }
}
