<?php

namespace App\Services\Network;

use App\Enums\CoreJointType;
use App\Enums\NetworkNodeType;
use App\Enums\OdcSplitterRatio;
use App\Enums\OdcSplitterRouteMode;
use App\Enums\OdcSplitterTargetType;
use App\Models\CoreJoint;
use App\Models\FiberCable;
use App\Models\FiberCore;
use App\Models\NetworkNode;
use App\Models\Odc;
use App\Models\OdcSplitter;
use App\Models\OdcSplitterOutput;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class OdcDistributionService
{
    public function __construct(
        private readonly CoreJointService $jointService,
        private readonly CableActivationService $cableActivationService,
    ) {}

    /** @return array<string, mixed> */
    public function formPayload(Odc $odc): array
    {
        $odc->load([
            'networkNode.parent',
            'splitters.inputCable',
            'splitters.outputs.targetNode',
            'splitters.outputs.outboundCable',
            'splitters.outputs.downstreamCable',
            'splitters.outputs.coreJoint',
            'odps.networkNode',
        ]);

        $nodeId = (int) $odc->network_node_id;

        return [
            'splitters' => $odc->splitters->map(fn (OdcSplitter $splitter) => [
                'id' => $splitter->id,
                'label' => $splitter->label,
                'input_cable_id' => $splitter->input_cable_id,
                'input_cable_code' => $splitter->inputCable?->code,
                'input_core_number' => $splitter->input_core_number,
                'split_ratio' => $splitter->split_ratio->value,
                'outputs' => $splitter->outputs->map(fn (OdcSplitterOutput $output) => $this->formatOutputPayload($splitter, $output))->values()->all(),
            ])->values()->all(),
            'route_summary' => $this->buildRouteSummary($odc),
            'inbound_cables' => $this->inboundCableOptions($odc),
            'child_odps' => $odc->odps->map(fn ($odp) => [
                'id' => $odp->network_node_id,
                'code' => $odp->code,
                'name' => $odp->networkNode?->name,
            ])->values()->all(),
            'sibling_odcs' => $this->siblingOdcOptions($odc),
            'outbound_cables' => $this->cableOptionsAtNode($nodeId),
            'planned_cables' => $this->cableActivationService->plannedCablesAtNode($nodeId),
        ];
    }

    /** @return array{inbound: list<array<string, mixed>>, outbound: list<array<string, mixed>>} */
    public function buildRouteSummary(Odc $odc): array
    {
        $odc->loadMissing([
            'networkNode',
            'splitters.inputCable',
            'splitters.outputs.targetNode',
            'splitters.outputs.outboundCable',
            'splitters.outputs.downstreamCable',
            'splitters.outputs.coreJoint',
        ]);

        $odcNodeId = (int) $odc->network_node_id;
        $linkedJointIds = $odc->splitters
            ->flatMap(fn (OdcSplitter $splitter) => $splitter->outputs->pluck('core_joint_id'))
            ->filter()
            ->values();

        $inbound = CoreJoint::query()
            ->where('odc_id', $odc->id)
            ->where('joint_type', CoreJointType::OtbToOdc->value)
            ->where('status', 'active')
            ->with(['sourceNode', 'targetNode', 'coreA.cable', 'coreB.cable'])
            ->orderBy('core_number')
            ->get()
            ->map(fn (CoreJoint $joint) => $this->formatJointSummaryRow($joint, 'inbound'))
            ->values()
            ->all();

        $outbound = collect();

        foreach ($odc->splitters as $splitter) {
            foreach ($splitter->outputs as $output) {
                if (! $this->outputIsConfigured($output)) {
                    continue;
                }

                $outbound->push($this->formatSplitterOutputSummaryRow($splitter, $output));
            }
        }

        $directOutbound = CoreJoint::query()
            ->where('source_node_id', $odcNodeId)
            ->whereIn('joint_type', [CoreJointType::OdcToOdp->value, CoreJointType::OdcToOdc->value])
            ->where('status', 'active')
            ->when($linkedJointIds->isNotEmpty(), fn ($query) => $query->whereNotIn('id', $linkedJointIds))
            ->with(['targetNode', 'coreA.cable', 'coreB.cable'])
            ->orderBy('core_number')
            ->get()
            ->map(fn (CoreJoint $joint) => $this->formatJointSummaryRow($joint, 'outbound'));

        return [
            'inbound' => $inbound,
            'outbound' => $outbound->merge($directOutbound)->values()->all(),
        ];
    }

    /** @return array<string, mixed> */
    private function formatOutputPayload(OdcSplitter $splitter, OdcSplitterOutput $output): array
    {
        $row = $this->formatSplitterOutputSummaryRow($splitter, $output);

        return [
            'id' => $output->id,
            'output_port' => $output->output_port,
            'target_type' => $output->target_type?->value,
            'target_node_id' => $output->target_node_id,
            'target_code' => $row['target_code'],
            'route_mode' => $output->route_mode?->value ?? OdcSplitterRouteMode::Direct->value,
            'outbound_cable_id' => $output->outbound_cable_id,
            'outbound_cable_code' => $row['cable_code'],
            'outbound_core_number' => $output->outbound_core_number,
            'downstream_cable_id' => $output->downstream_cable_id,
            'downstream_cable_code' => $row['downstream_cable_code'],
            'downstream_core_number' => $output->downstream_core_number,
            'joint_code' => $row['joint_code'],
            'summary' => $row['summary'],
        ];
    }

    /** @return array<string, mixed> */
    private function formatSplitterOutputSummaryRow(OdcSplitter $splitter, OdcSplitterOutput $output): array
    {
        $routeMode = $output->route_mode ?? OdcSplitterRouteMode::Direct;
        $targetCode = $output->targetNode?->code ?? '—';
        $cableCode = $output->outboundCable?->code ?? '—';
        $downstreamCableCode = $output->downstreamCable?->code;
        $jointCode = $output->coreJoint?->code;
        $inputCableCode = $splitter->inputCable?->code ?? '—';
        $splitterLabel = $splitter->label ?: __('hfnms.odc_dist_splitter').' #'.($splitter->sort_order + 1);
        $targetType = $output->target_type?->label() ?? '—';

        if ($routeMode === OdcSplitterRouteMode::ViaJoint) {
            $summary = sprintf(
                '%s core %d → %s P%d → %s core %d → %s → %s core %d → %s %s',
                $inputCableCode,
                $splitter->input_core_number,
                $splitter->split_ratio->value,
                $output->output_port,
                $cableCode,
                $output->outbound_core_number ?? 0,
                __('hfnms.odc_dist_route_mode_via_joint_short'),
                $downstreamCableCode ?? '—',
                $output->downstream_core_number ?? 0,
                $targetType,
                $targetCode,
            );
        } else {
            $summary = sprintf(
                '%s core %d → %s P%d → %s core %d → %s %s',
                $inputCableCode,
                $splitter->input_core_number,
                $splitter->split_ratio->value,
                $output->output_port,
                $cableCode,
                $output->outbound_core_number ?? 0,
                $targetType,
                $targetCode,
            );
        }

        if ($jointCode) {
            $summary .= ' · '.__('hfnms.joint_code').': '.$jointCode;
        }

        return [
            'splitter_label' => $splitterLabel,
            'split_ratio' => $splitter->split_ratio->value,
            'input_cable_code' => $inputCableCode,
            'input_core_number' => $splitter->input_core_number,
            'port' => $output->output_port,
            'route_mode' => $routeMode->label(),
            'target_type' => $targetType,
            'target_code' => $targetCode,
            'cable_code' => $cableCode,
            'core_number' => $output->outbound_core_number,
            'downstream_cable_code' => $downstreamCableCode,
            'downstream_core_number' => $output->downstream_core_number,
            'joint_code' => $jointCode,
            'joint_type' => $output->target_type?->jointType()->label(),
            'summary' => $summary,
        ];
    }

    /** @return array<string, mixed> */
    private function formatJointSummaryRow(CoreJoint $joint, string $direction): array
    {
        $cableA = $joint->coreA?->cable?->code ?? '—';
        $cableB = $joint->coreB?->cable?->code ?? '—';
        $coreNumber = $joint->core_number ?? $joint->coreA?->core_number ?? 0;
        $sourceCode = $joint->sourceNode?->code ?? '—';
        $targetCode = $joint->targetNode?->code ?? '—';

        $summary = $direction === 'inbound'
            ? sprintf('%s → %s · %s / %s · core %d', $sourceCode, $targetCode, $cableA, $cableB, $coreNumber)
            : sprintf('%s → %s · %s core %d · %s', $sourceCode, $targetCode, $cableA === $cableB ? $cableA : $cableA.' / '.$cableB, $coreNumber, $joint->joint_type?->label());

        return [
            'joint_code' => $joint->code,
            'joint_type' => $joint->joint_type?->label(),
            'source_code' => $sourceCode,
            'target_code' => $targetCode,
            'cable_a_code' => $cableA,
            'cable_b_code' => $cableB,
            'core_number' => $coreNumber,
            'summary' => $summary,
        ];
    }

    /** @return list<array{id: int, code: string, label: string}> */
    public function inboundCableOptions(Odc $odc): array
    {
        $nodeId = (int) $odc->network_node_id;
        $otbNodeId = (int) ($odc->networkNode?->parent_id ?? 0);

        return $this->cablesBetweenNodes($nodeId, $otbNodeId ?: null)
            ->map(fn (FiberCable $cable) => [
                'id' => $cable->id,
                'code' => $cable->code,
                'label' => $cable->code.' — '.$cable->name,
            ])
            ->values()
            ->all();
    }

    /** @return list<array{id: int, code: string, label: string, core_count: int}> */
    public function cableOptionsAtNode(int $nodeId): array
    {
        return FiberCable::query()
            ->where(function ($query) use ($nodeId) {
                $query->where('start_node_id', $nodeId)
                    ->orWhere('end_node_id', $nodeId);
            })
            ->orderBy('code')
            ->get(['id', 'code', 'name', 'core_count', 'start_node_id', 'end_node_id'])
            ->map(function (FiberCable $cable) use ($nodeId) {
                $atStart = (int) $cable->start_node_id === $nodeId;
                $atEnd = (int) $cable->end_node_id === $nodeId;
                $otherNodeId = $atStart ? $cable->end_node_id : ($atEnd ? $cable->start_node_id : null);
                $openEnded = $otherNodeId === null;

                return [
                    'id' => $cable->id,
                    'code' => $cable->code,
                    'label' => $cable->code.' — '.$cable->name
                        .($openEnded ? ' ('.__('hfnms.cable_open_ended').')' : '')
                        .($cable->status === 'planned' ? ' (planned)' : ''),
                    'core_count' => $cable->core_count,
                    'status' => $cable->status,
                    'is_planned' => $cable->status === 'planned',
                    'is_open_ended' => $openEnded,
                    'other_node_id' => $otherNodeId,
                ];
            })
            ->values()
            ->all();
    }

    /** @return list<array{core_number: int, status: string, label: string}> */
    public function coreOptionsForCable(int $cableId): array
    {
        return FiberCore::query()
            ->where('cable_id', $cableId)
            ->orderBy('core_number')
            ->get(['id', 'core_number', 'status'])
            ->map(fn (FiberCore $core) => [
                'id' => $core->id,
                'core_number' => $core->core_number,
                'status' => $core->status->value,
                'label' => 'Core '.$core->core_number.' ('.$core->status->value.')',
            ])
            ->values()
            ->all();
    }

    /** @return list<array{id: int, code: string, name: string|null}> */
    public function targetOptions(Odc $odc, string $targetType): array
    {
        return match (OdcSplitterTargetType::from($targetType)) {
            OdcSplitterTargetType::Odp => $odc->odps()
                ->with('networkNode:id,code,name')
                ->orderBy('code')
                ->get()
                ->map(fn ($odp) => [
                    'id' => $odp->network_node_id,
                    'code' => $odp->code,
                    'name' => $odp->networkNode?->name,
                ])
                ->values()
                ->all(),
            OdcSplitterTargetType::Odc => $this->siblingOdcOptions($odc),
        };
    }

    /** @param array<string, mixed> $data */
    public function sync(Odc $odc, array $data): void
    {
        DB::transaction(function () use ($odc, $data) {
            $existingIds = [];
            $splitters = $data['splitters'] ?? [];

            foreach ($splitters as $index => $splitterData) {
                $ratio = OdcSplitterRatio::from($splitterData['split_ratio'] ?? OdcSplitterRatio::Ratio1x4->value);
                $this->validateInputCable($odc, (int) ($splitterData['input_cable_id'] ?? 0));

                $splitter = isset($splitterData['id'])
                    ? OdcSplitter::query()->where('odc_id', $odc->id)->findOrFail((int) $splitterData['id'])
                    : new OdcSplitter(['odc_id' => $odc->id]);

                $splitter->fill([
                    'label' => $splitterData['label'] ?? null,
                    'input_cable_id' => (int) $splitterData['input_cable_id'],
                    'input_core_number' => (int) $splitterData['input_core_number'],
                    'split_ratio' => $ratio,
                    'sort_order' => $index,
                ]);
                $splitter->save();
                $existingIds[] = $splitter->id;

                $this->syncSplitterOutputs($odc, $splitter, $splitterData['outputs'] ?? [], $ratio);
            }

            $removed = OdcSplitter::query()
                ->where('odc_id', $odc->id)
                ->whereNotIn('id', $existingIds)
                ->get();

            foreach ($removed as $splitter) {
                $this->deleteSplitter($splitter);
            }

            $this->syncOdcCoreCounters($odc);
        });
    }

    /** @param list<array<string, mixed>> $outputs */
    private function syncSplitterOutputs(Odc $odc, OdcSplitter $splitter, array $outputs, OdcSplitterRatio $ratio): void
    {
        $keptOutputIds = [];
        $sourceNodeId = (int) $odc->network_node_id;
        $maxPorts = $ratio->outputCount();

        for ($port = 1; $port <= $maxPorts; $port++) {
            $outputData = collect($outputs)->first(
                fn (array $row) => (int) ($row['output_port'] ?? 0) === $port
            ) ?? ['output_port' => $port];
            $output = isset($outputData['id'])
                ? OdcSplitterOutput::query()->where('odc_splitter_id', $splitter->id)->find((int) $outputData['id'])
                : null;

            if (! $output) {
                $output = new OdcSplitterOutput([
                    'odc_splitter_id' => $splitter->id,
                    'output_port' => $port,
                ]);
            }

            $targetType = ! empty($outputData['target_type'])
                ? OdcSplitterTargetType::from($outputData['target_type'])
                : null;
            $targetNodeId = (int) ($outputData['target_node_id'] ?? 0);
            $routeMode = OdcSplitterRouteMode::from($outputData['route_mode'] ?? OdcSplitterRouteMode::Direct->value);
            $outboundCableId = (int) ($outputData['outbound_cable_id'] ?? 0);
            $outboundCoreNumber = (int) ($outputData['outbound_core_number'] ?? 0);
            $downstreamCableId = (int) ($outputData['downstream_cable_id'] ?? 0);
            $downstreamCoreNumber = (int) ($outputData['downstream_core_number'] ?? 0);

            $isConfigured = $targetType && $targetNodeId && $outboundCableId && $outboundCoreNumber > 0;
            if ($routeMode === OdcSplitterRouteMode::ViaJoint) {
                $isConfigured = $isConfigured && $downstreamCableId && $downstreamCoreNumber > 0;
            }

            if (! $isConfigured) {
                if ($output->exists && $output->core_joint_id) {
                    $this->jointService->delete($output->coreJoint);
                }
                if ($output->exists) {
                    $output->delete();
                }

                continue;
            }

            $this->validateOutput(
                $odc,
                $targetType,
                $targetNodeId,
                $outboundCableId,
                $sourceNodeId,
                $routeMode,
                $downstreamCableId,
            );

            $joint = $this->upsertOutputJoint(
                $output,
                $targetType,
                $routeMode,
                $sourceNodeId,
                $targetNodeId,
                $outboundCableId,
                $outboundCoreNumber,
                $downstreamCableId,
                $downstreamCoreNumber,
                $odc,
            );

            $output->fill([
                'output_port' => $port,
                'target_type' => $targetType,
                'target_node_id' => $targetNodeId,
                'route_mode' => $routeMode,
                'outbound_cable_id' => $outboundCableId,
                'outbound_core_number' => $outboundCoreNumber,
                'downstream_cable_id' => $routeMode === OdcSplitterRouteMode::ViaJoint ? $downstreamCableId : null,
                'downstream_core_number' => $routeMode === OdcSplitterRouteMode::ViaJoint ? $downstreamCoreNumber : null,
                'core_joint_id' => $joint->id,
            ]);
            $output->save();

            $cableIds = [$outboundCableId];
            if ($routeMode === OdcSplitterRouteMode::ViaJoint) {
                $cableIds[] = $downstreamCableId;
            }
            $this->cableActivationService->activateMany($cableIds);

            $keptOutputIds[] = $output->id;
        }

        $removedOutputs = OdcSplitterOutput::query()
            ->where('odc_splitter_id', $splitter->id)
            ->whereNotIn('id', $keptOutputIds)
            ->get();

        foreach ($removedOutputs as $removed) {
            if ($removed->core_joint_id) {
                $this->jointService->delete($removed->coreJoint);
            }
            $removed->delete();
        }
    }

    private function upsertOutputJoint(
        OdcSplitterOutput $output,
        OdcSplitterTargetType $targetType,
        OdcSplitterRouteMode $routeMode,
        int $sourceNodeId,
        int $targetNodeId,
        int $outboundCableId,
        int $outboundCoreNumber,
        int $downstreamCableId,
        int $downstreamCoreNumber,
        Odc $odc,
    ): CoreJoint {
        if ($routeMode === OdcSplitterRouteMode::ViaJoint) {
            $coreA = $this->resolveCoreOnCableAtNode($outboundCableId, $outboundCoreNumber, $sourceNodeId);
            $coreB = $this->resolveCoreOnCableAtNode($downstreamCableId, $downstreamCoreNumber, $targetNodeId);

            if (! $coreA || ! $coreB) {
                throw new InvalidArgumentException(__('hfnms.odc_dist_core_not_on_cable'));
            }

            $pair = ['source' => $coreA, 'target' => $coreB];
            $coreNumber = $outboundCoreNumber;
        } else {
            $pair = $this->resolveCorePairOnCable($outboundCableId, $outboundCoreNumber, $sourceNodeId, $targetNodeId);

            if (! $pair) {
                throw new InvalidArgumentException(__('hfnms.odc_dist_core_not_on_cable'));
            }

            $coreNumber = $outboundCoreNumber;
        }

        $jointType = $targetType->jointType();
        $payload = [
            'joint_type' => $jointType->value,
            'source_node_id' => $sourceNodeId,
            'target_node_id' => $targetNodeId,
            'core_number' => $coreNumber,
            'fiber_core_a_id' => $pair['source']->id,
            'fiber_core_b_id' => $pair['target']->id,
            'status' => 'active',
            'spliced_at' => now(),
        ];

        if ($output->core_joint_id && $output->coreJoint) {
            return $this->jointService->update($output->coreJoint, $payload);
        }

        $payload['code'] = sprintf(
            'SPL-%s-P%d',
            $odc->code,
            $output->output_port
        );

        return $this->jointService->create($payload);
    }

    /**
     * @return array{source: FiberCore, target: FiberCore}|null
     */
    private function resolveCorePairOnCable(
        int $cableId,
        int $coreNumber,
        int $sourceNodeId,
        int $targetNodeId,
    ): ?array {
        $cable = FiberCable::query()->find($cableId);

        if (! $cable) {
            return null;
        }

        $nodeIds = [(int) $cable->start_node_id, (int) $cable->end_node_id];
        if (! in_array($sourceNodeId, $nodeIds, true) || ! in_array($targetNodeId, $nodeIds, true)) {
            return null;
        }

        $core = FiberCore::query()
            ->where('cable_id', $cableId)
            ->where('core_number', $coreNumber)
            ->first();

        if (! $core) {
            return null;
        }

        return ['source' => $core, 'target' => $core];
    }

    private function resolveCoreOnCableAtNode(int $cableId, int $coreNumber, int $nodeId): ?FiberCore
    {
        $cable = FiberCable::query()->find($cableId);

        if (! $cable) {
            return null;
        }

        $endpoints = [(int) $cable->start_node_id, (int) $cable->end_node_id];
        if (! in_array($nodeId, $endpoints, true)) {
            return null;
        }

        return FiberCore::query()
            ->where('cable_id', $cableId)
            ->where('core_number', $coreNumber)
            ->first();
    }

    private function outputIsConfigured(OdcSplitterOutput $output): bool
    {
        $routeMode = $output->route_mode ?? OdcSplitterRouteMode::Direct;

        if (! $output->target_node_id || ! $output->outbound_cable_id || ! $output->outbound_core_number) {
            return false;
        }

        if ($routeMode === OdcSplitterRouteMode::ViaJoint) {
            return (bool) ($output->downstream_cable_id && $output->downstream_core_number);
        }

        return true;
    }

    private function validateInputCable(Odc $odc, int $cableId): void
    {
        if (! $cableId) {
            throw new InvalidArgumentException(__('hfnms.odc_dist_input_cable_required'));
        }

        $nodeId = (int) $odc->network_node_id;
        $exists = FiberCable::query()
            ->where('id', $cableId)
            ->where(function ($query) use ($nodeId) {
                $query->where('start_node_id', $nodeId)
                    ->orWhere('end_node_id', $nodeId);
            })
            ->exists();

        if (! $exists) {
            throw new InvalidArgumentException(__('hfnms.odc_dist_input_cable_invalid'));
        }
    }

    private function validateOutput(
        Odc $odc,
        OdcSplitterTargetType $targetType,
        int $targetNodeId,
        int $outboundCableId,
        int $sourceNodeId,
        OdcSplitterRouteMode $routeMode = OdcSplitterRouteMode::Direct,
        int $downstreamCableId = 0,
    ): void {
        $target = NetworkNode::query()->find($targetNodeId);

        if (! $target || $target->type !== $targetType->jointType()->targetType()) {
            throw new InvalidArgumentException(__('hfnms.odc_dist_invalid_target'));
        }

        if ($targetType === OdcSplitterTargetType::Odp) {
            $valid = $odc->odps()->where('network_node_id', $targetNodeId)->exists();
            if (! $valid) {
                throw new InvalidArgumentException(__('hfnms.odc_dist_odp_not_child'));
            }
        }

        if ($targetType === OdcSplitterTargetType::Odc && $targetNodeId === $sourceNodeId) {
            throw new InvalidArgumentException(__('hfnms.odc_dist_same_odc_target'));
        }

        $outboundCable = FiberCable::query()->find($outboundCableId);
        if (! $outboundCable) {
            throw new InvalidArgumentException(__('hfnms.odc_dist_outbound_cable_invalid'));
        }

        $outboundEndpoints = [(int) $outboundCable->start_node_id, (int) $outboundCable->end_node_id];

        if ($routeMode === OdcSplitterRouteMode::ViaJoint) {
            if (! in_array($sourceNodeId, $outboundEndpoints, true)) {
                throw new InvalidArgumentException(__('hfnms.odc_dist_outbound_cable_not_at_odc'));
            }

            $downstreamCable = FiberCable::query()->find($downstreamCableId);
            if (! $downstreamCable) {
                throw new InvalidArgumentException(__('hfnms.odc_dist_downstream_cable_invalid'));
            }

            $downstreamEndpoints = [(int) $downstreamCable->start_node_id, (int) $downstreamCable->end_node_id];
            if (! in_array($targetNodeId, $downstreamEndpoints, true)) {
                throw new InvalidArgumentException(__('hfnms.odc_dist_downstream_cable_not_at_target'));
            }

            return;
        }

        if (! in_array($sourceNodeId, $outboundEndpoints, true) || ! in_array($targetNodeId, $outboundEndpoints, true)) {
            throw new InvalidArgumentException(__('hfnms.odc_dist_cable_not_between_nodes'));
        }
    }

    private function deleteSplitter(OdcSplitter $splitter): void
    {
        foreach ($splitter->outputs as $output) {
            if ($output->core_joint_id) {
                $this->jointService->delete($output->coreJoint);
            }
        }

        $splitter->outputs()->delete();
        $splitter->delete();
    }

    private function syncOdcCoreCounters(Odc $odc): void
    {
        $odcNodeId = (int) $odc->network_node_id;

        $coresToOdp = CoreJoint::query()
            ->where('source_node_id', $odcNodeId)
            ->whereIn('joint_type', [CoreJointType::OdcToOdp->value, CoreJointType::OdcToOdc->value])
            ->where('status', 'active')
            ->pluck('core_number')
            ->unique()
            ->count();

        $odc->update(['cores_to_odp' => $coresToOdp]);
    }

    /** @return list<array{id: int, code: string, name: string|null}> */
    private function siblingOdcOptions(Odc $odc): array
    {
        $otbNodeId = (int) ($odc->networkNode?->parent_id ?? 0);
        $selfNodeId = (int) $odc->network_node_id;

        if (! $otbNodeId) {
            return [];
        }

        return NetworkNode::query()
            ->where('type', NetworkNodeType::Odc)
            ->where('parent_id', $otbNodeId)
            ->where('id', '!=', $selfNodeId)
            ->orderBy('code')
            ->get(['id', 'code', 'name'])
            ->map(fn (NetworkNode $node) => [
                'id' => $node->id,
                'code' => $node->code,
                'name' => $node->name,
            ])
            ->values()
            ->all();
    }

    /** @return Collection<int, FiberCable> */
    private function cablesBetweenNodes(int $nodeA, ?int $nodeB): Collection
    {
        $query = FiberCable::query()->orderBy('code');

        if ($nodeB) {
            $query->where(function ($inner) use ($nodeA, $nodeB) {
                $inner->where(function ($q) use ($nodeA, $nodeB) {
                    $q->where('start_node_id', $nodeA)->where('end_node_id', $nodeB);
                })->orWhere(function ($q) use ($nodeA, $nodeB) {
                    $q->where('start_node_id', $nodeB)->where('end_node_id', $nodeA);
                });
            });
        } else {
            $query->where(function ($inner) use ($nodeA) {
                $inner->where('start_node_id', $nodeA)
                    ->orWhere('end_node_id', $nodeA);
            });
        }

        return $query->get();
    }
}
