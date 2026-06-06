<?php

namespace App\Http\Controllers\Network;

use App\Http\Controllers\Controller;
use App\Models\FiberCable;
use App\Services\Network\QrCodeService;
use Illuminate\View\View;

class CableController extends Controller
{
    public function __construct(
        private readonly QrCodeService $qrCodeService,
    ) {}
    public function index(): View
    {
        $cables = FiberCable::query()
            ->with(['startNode', 'endNode'])
            ->withCount('cores')
            ->latest()
            ->paginate(15);

        return view('network.cables.index', compact('cables'));
    }

    public function show(FiberCable $cable): View
    {
        $cable->load([
            'startNode',
            'endNode',
            'tubes.tubeColor',
            'cores.tubeColor',
        ]);

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
}
