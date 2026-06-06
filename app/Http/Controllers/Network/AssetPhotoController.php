<?php

namespace App\Http\Controllers\Network;

use App\Enums\NetworkNodeType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Network\StoreAssetPhotoRequest;
use App\Models\AssetPhoto;
use App\Services\Network\AssetPhotoService;
use App\Services\Network\NetworkAssetService;
use Illuminate\Http\RedirectResponse;

class AssetPhotoController extends Controller
{
    public function __construct(
        private readonly AssetPhotoService $photoService,
        private readonly NetworkAssetService $assetService,
    ) {}

    public function store(StoreAssetPhotoRequest $request, string $type, int $asset): RedirectResponse
    {
        $nodeType = NetworkNodeType::fromRouteSlug($type) ?? abort(404);
        $record = $this->assetService->find($nodeType, $asset);
        $node = $record->networkNode;

        $this->photoService->store(
            $node,
            $request->file('photo'),
            $request->string('photo_type')->toString(),
            $request->string('caption')->toString() ?: null,
        );

        return back()->with('success', __('hfnms.photo_uploaded'));
    }

    public function destroy(AssetPhoto $photo): RedirectResponse
    {
        $this->authorize('network.edit');

        $this->photoService->delete($photo);

        return back()->with('success', __('hfnms.photo_deleted'));
    }
}
