<?php

namespace App\Http\Controllers\Network;

use App\Http\Controllers\Controller;
use App\Http\Requests\Network\StoreCableRequest;
use App\Http\Requests\Network\UpdateCableRequest;
use App\Models\FiberCable;
use App\Services\Network\CableActivationService;
use App\Services\Network\CableManagementService;
use App\Services\Network\QrCodeService;
use App\Support\GpsCoordinateParser;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class CableController extends Controller
{
    public function __construct(
        private readonly CableManagementService $cableService,
        private readonly CableActivationService $cableActivationService,
        private readonly QrCodeService $qrCodeService,
    ) {}

    public function index(): View
    {
        $cables = $this->cableService->paginate();

        return view('network.cables.index', compact('cables'));
    }

    public function create(): View
    {
        return view('network.cables.form', [
            'cable' => null,
            'nodes' => $this->cableService->nodeOptions(),
            'gisMapUrl' => route('gis.index'),
        ]);
    }

    public function store(StoreCableRequest $request): RedirectResponse
    {
        try {
            $cable = $this->cableService->create($this->mapCableData($request->validated()));
        } catch (\InvalidArgumentException $e) {
            return back()->withInput()->withErrors(['form' => $e->getMessage()]);
        }

        return redirect()
            ->route('cables.show', $cable)
            ->with('success', __('hfnms.cable_created'));
    }

    public function show(FiberCable $cable): View
    {
        $cable = $this->cableService->find($cable->id);
        $tubes = $cable->tubes->sortBy('tube_number');
        $coresByTube = $cable->cores->groupBy('tube_number');

        return view('network.cables.show', [
            'cable' => $cable,
            'tubes' => $tubes,
            'coresByTube' => $coresByTube,
            'qrUrl' => $this->qrCodeService->publicUrl($cable->qr_code_path),
            'scanUrl' => $this->qrCodeService->scanUrlForCable($cable),
        ]);
    }

    public function edit(FiberCable $cable): View
    {
        return view('network.cables.form', [
            'cable' => $this->cableService->find($cable->id),
            'nodes' => $this->cableService->nodeOptions(),
            'gisMapUrl' => route('gis.index'),
        ]);
    }

    public function update(UpdateCableRequest $request, FiberCable $cable): RedirectResponse
    {
        try {
            $this->cableService->update($cable, $this->mapCableData($request->validated()));
        } catch (\InvalidArgumentException $e) {
            return back()->withInput()->withErrors(['form' => $e->getMessage()]);
        }

        return redirect()
            ->route('cables.show', $cable)
            ->with('success', __('hfnms.cable_updated'));
    }

    public function activate(FiberCable $cable): RedirectResponse
    {
        try {
            $this->cableActivationService->activate($cable);
        } catch (\InvalidArgumentException $e) {
            return back()->withErrors(['form' => $e->getMessage()]);
        }

        return back()->with('success', __('hfnms.cable_activated'));
    }

    public function destroy(FiberCable $cable): RedirectResponse
    {
        try {
            $this->cableService->delete($cable);
        } catch (\InvalidArgumentException $e) {
            return back()->withErrors(['form' => $e->getMessage()]);
        }

        return redirect()
            ->route('cables.index')
            ->with('success', __('hfnms.cable_deleted'));
    }

    /** @param array<string, mixed> $data */
    private function mapCableData(array $data): array
    {
        $text = $data['route_geometry_text'] ?? null;
        $snapToRoads = filter_var($data['snap_to_roads'] ?? true, FILTER_VALIDATE_BOOLEAN);
        unset($data['route_geometry_text'], $data['snap_to_roads']);

        if (is_string($text) && trim($text) !== '') {
            $data['route_geometry'] = $this->parseRouteGeometry($text);
        } elseif ($snapToRoads) {
            $data['snap_to_roads'] = true;
        } else {
            $data['route_geometry'] = null;
        }

        return $data;
    }

    /** @return list<array{lat: float, lng: float}>|null */
    private function parseRouteGeometry(string $text): ?array
    {
        $lines = preg_split('/\r\n|\r|\n/', trim($text)) ?: [];
        $points = [];

        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }

            $parsed = GpsCoordinateParser::parse($line);
            if ($parsed) {
                $points[] = ['lat' => $parsed['lat'], 'lng' => $parsed['lng']];
            }
        }

        return count($points) >= 2 ? $points : null;
    }
}
