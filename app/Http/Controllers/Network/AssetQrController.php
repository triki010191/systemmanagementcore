<?php

namespace App\Http\Controllers\Network;

use App\Enums\NetworkNodeType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Network\StoreAssetPhotoRequest;
use App\Models\AssetPhoto;
use App\Models\FiberCable;
use App\Services\Network\AssetPhotoService;
use App\Services\Network\NetworkAssetService;
use App\Services\Network\QrCodeService;
use Illuminate\Http\RedirectResponse;
use RuntimeException;

class AssetQrController extends Controller
{
    public function __construct(
        private readonly QrCodeService $qrCodeService,
        private readonly NetworkAssetService $assetService,
    ) {}

    public function generateNode(string $type, int $asset): RedirectResponse
    {
        $this->authorize('network.edit');

        $nodeType = NetworkNodeType::fromRouteSlug($type) ?? abort(404);
        $record = $this->assetService->find($nodeType, $asset);
        $node = $record->networkNode;

        try {
            $this->qrCodeService->generateForNode($node);
        } catch (RuntimeException $e) {
            return back()->withErrors(['form' => $e->getMessage()]);
        }

        return back()->with('success', __('hfnms.qr_generated'));
    }

    public function generateCable(FiberCable $cable): RedirectResponse
    {
        $this->authorize('cable.manage');

        $this->qrCodeService->generateForCable($cable);

        return back()->with('success', __('hfnms.qr_generated'));
    }
}
