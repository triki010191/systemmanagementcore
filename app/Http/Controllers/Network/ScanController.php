<?php

namespace App\Http\Controllers\Network;

use App\Exports\CustomerTemplateExport;
use App\Exports\OdpTemplateExport;
use App\Http\Controllers\Controller;
use App\Imports\CustomerImport;
use App\Imports\OdpImport;
use App\Models\FiberCable;
use App\Models\NetworkNode;
use App\Services\Network\AssetPhotoService;
use App\Services\Network\QrCodeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use RuntimeException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ScanController extends Controller
{
    public function __construct(
        private readonly QrCodeService $qrCodeService,
        private readonly NetworkAssetService $assetService,
        private readonly AssetPhotoService $photoService,
    ) {}

    public function show(string $uuid): View
    {
        $node = NetworkNode::query()
            ->where('uuid', $uuid)
            ->with(['parent'])
            ->firstOrFail();

        return view('network.scan.show', [
            'node' => $node,
            'photos' => $this->photoService->forNode($node),
            'qrUrl' => $this->qrCodeService->publicUrl($node->qr_code_path),
        ]);
    }

    public function cable(string $code): View
    {
        $cable = FiberCable::query()
            ->with(['startNode', 'endNode'])
            ->where('code', $code)
            ->firstOrFail();

        return view('network.scan.cable', [
            'cable' => $cable,
            'qrUrl' => $this->qrCodeService->publicUrl($cable->qr_code_path),
        ]);
    }
}
