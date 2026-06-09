<?php

namespace App\Http\Controllers\Network;

use App\Enums\CoreJointType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Network\StoreCoreJointRequest;
use App\Http\Requests\Network\UpdateCoreJointRequest;
use App\Models\CoreJoint;
use App\Models\NetworkNode;
use App\Services\Network\CoreJointService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CoreJointController extends Controller
{
    public function __construct(
        private readonly CoreJointService $jointService,
    ) {}

    public function index(): View
    {
        $joints = $this->jointService->paginate();

        return view('network.core-joints.index', compact('joints'));
    }

    public function create(): View
    {
        return view('network.core-joints.form', $this->formData(null));
    }

    public function store(StoreCoreJointRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['code'] = ! empty($data['code']) ? $data['code'] : $this->suggestCode();
        $data['spliced_at'] = $data['spliced_at'] ?? now();

        try {
            $this->jointService->create($data);
        } catch (\InvalidArgumentException $e) {
            return back()->withInput()->withErrors(['form' => $e->getMessage()]);
        }

        return redirect()
            ->route('core-joints.index')
            ->with('success', __('hfnms.joint_created'));
    }

    public function edit(CoreJoint $coreJoint): View
    {
        return view('network.core-joints.form', $this->formData($this->jointService->find($coreJoint->id)));
    }

    public function update(UpdateCoreJointRequest $request, CoreJoint $coreJoint): RedirectResponse
    {
        try {
            $this->jointService->update($coreJoint, $request->validated());
        } catch (\InvalidArgumentException $e) {
            return back()->withInput()->withErrors(['form' => $e->getMessage()]);
        }

        return redirect()
            ->route('core-joints.index')
            ->with('success', __('hfnms.joint_updated'));
    }

    public function destroy(CoreJoint $coreJoint): RedirectResponse
    {
        $this->jointService->delete($coreJoint);

        return redirect()
            ->route('core-joints.index')
            ->with('success', __('hfnms.joint_deleted'));
    }

    public function options(Request $request): JsonResponse
    {
        $jointType = CoreJointType::tryFrom((string) $request->query('joint_type', ''));
        $sourceNodeId = (int) $request->query('source_node_id');
        $targetNodeId = (int) $request->query('target_node_id');
        $exceptId = $request->query('except') ? (int) $request->query('except') : null;

        if (! $jointType) {
            return response()->json(['sources' => [], 'targets' => [], 'cores' => []]);
        }

        if ($request->boolean('targets_only')) {
            return response()->json([
                'targets' => $this->jointService->targetNodeOptions($jointType, $sourceNodeId ?: null)
                    ->map(fn ($node) => $this->mapNodeOption($node))->values(),
            ]);
        }

        if ($jointType === CoreJointType::OdcToOdp && $request->boolean('odcs_only')) {
            $otbNodeId = (int) $request->query('otb_node_id');

            return response()->json([
                'sources' => $this->jointService->odcNodeOptionsForOtb($otbNodeId ?: null)
                    ->map(fn ($node) => $this->mapNodeOption($node))->values(),
            ]);
        }

        if ($jointType === CoreJointType::OdcToOdp && ! $sourceNodeId && ! $targetNodeId) {
            return response()->json([
                'otbs' => $this->jointService->otbNodeOptions()
                    ->map(fn ($node) => $this->mapNodeOption($node))->values(),
            ]);
        }

        if ($request->boolean('cables_at_node')) {
            $nodeId = (int) $request->query('node_id');

            return response()->json([
                'cables' => $nodeId ? $this->jointService->cableOptionsAtNode($nodeId) : [],
            ]);
        }

        if ($request->filled('cable_id')) {
            return response()->json([
                'cores' => $this->jointService->coreOptionsForCable((int) $request->query('cable_id')),
            ]);
        }

        if ($sourceNodeId && $targetNodeId) {
            try {
                $cores = $this->jointService->coreOptionsForJoint($jointType, $sourceNodeId, $targetNodeId, $exceptId);

                return response()->json([
                    'cores' => $cores,
                    'max_core_number' => $this->jointService->maxSharedCoreNumber($sourceNodeId, $targetNodeId),
                    'hint' => $cores === []
                        ? $this->jointService->coreAvailabilityHint($sourceNodeId, $targetNodeId)
                        : null,
                ]);
            } catch (\InvalidArgumentException $e) {
                return response()->json([
                    'error' => $e->getMessage(),
                    'cores' => [],
                    'max_core_number' => 0,
                    'hint' => $e->getMessage(),
                ], 422);
            }
        }

        return response()->json([
            'sources' => $this->jointService->sourceNodeOptions($jointType)
                ->map(fn ($node) => $this->mapNodeOption($node))->values(),
        ]);
    }

    /** @return array<string, mixed> */
    private function mapNodeOption(NetworkNode $node): array
    {
        return [
            'id' => $node->id,
            'code' => $node->code,
            'name' => $node->name,
            'type' => $node->type->value,
            'latitude' => (float) $node->latitude,
            'longitude' => (float) $node->longitude,
            'parent_id' => $node->parent_id,
        ];
    }

    /** @return array<string, mixed> */
    private function formData(?CoreJoint $joint): array
    {
        $jointType = CoreJointType::tryFrom(old('joint_type', $joint?->joint_type?->value ?? CoreJointType::OtbToOdc->value))
            ?? CoreJointType::OtbToOdc;

        return [
            'joint' => $joint,
            'jointType' => $jointType,
            'sourceNodes' => $this->jointService->sourceNodeOptions($jointType),
            'targetNodes' => $this->jointService->targetNodeOptions(
                $jointType,
                (int) old('source_node_id', $joint?->source_node_id)
            ),
            'initialOtbId' => (int) old('otb_reference_id', $joint?->sourceNode?->parent_id ?? 0),
            'mapNodes' => $this->jointService->mapNodeMarkers(),
            'suggestedCode' => $joint ? null : $this->suggestCode(),
        ];
    }

    private function suggestCode(): string
    {
        $last = CoreJoint::query()->orderByDesc('id')->value('code');
        $number = 1;

        if ($last && preg_match('/(\d+)$/', $last, $matches)) {
            $number = ((int) $matches[1]) + 1;
        }

        return 'JC-'.str_pad((string) $number, 4, '0', STR_PAD_LEFT);
    }
}
