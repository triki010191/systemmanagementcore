<?php

namespace App\Http\Controllers\Network;

use App\Enums\NetworkNodeType;
use App\Enums\SplitterRatio;
use App\Http\Controllers\Controller;
use App\Http\Requests\Network\StoreNetworkAssetRequest;
use App\Http\Requests\Network\UpdateNetworkAssetRequest;
use App\Repositories\NetworkNodeRepository;
use App\Services\Network\AssetCodeGenerator;
use App\Services\Network\AssetPhotoService;
use App\Services\Network\NetworkAssetService;
use App\Services\Network\QrCodeService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use RuntimeException;

class AssetController extends Controller
{
    public function __construct(
        private readonly NetworkAssetService $assetService,
        private readonly NetworkNodeRepository $nodeRepository,
        private readonly AssetCodeGenerator $codeGenerator,
        private readonly QrCodeService $qrCodeService,
        private readonly AssetPhotoService $photoService,
    ) {}

    public function index(string $type): View
    {
        $nodeType = $this->resolveType($type);

        return view('network.assets.index', [
            'type' => $nodeType,
            'assets' => $this->assetService->paginate($nodeType),
        ]);
    }

    public function create(string $type): View
    {
        $nodeType = $this->resolveType($type);

        return view('network.assets.form', [
            'type' => $nodeType,
            'asset' => null,
            'parents' => $this->parentsFor($nodeType),
            'suggestedCode' => $nodeType->usesAutoCode() ? $this->codeGenerator->next($nodeType) : null,
            'splitterRatios' => SplitterRatio::cases(),
        ]);
    }

    public function store(StoreNetworkAssetRequest $request, string $type): RedirectResponse
    {
        $nodeType = $this->resolveType($type);

        try {
            $asset = $this->assetService->create($nodeType, $request->validated());
        } catch (RuntimeException $e) {
            return back()->withInput()->withErrors(['form' => $e->getMessage()]);
        }

        return redirect()
            ->route('network.assets.show', [$nodeType->routeSlug(), $asset->id])
            ->with('success', __('hfnms.asset_created'));
    }

    public function show(string $type, int $asset): View
    {
        $nodeType = $this->resolveType($type);
        $record = $this->assetService->find($nodeType, $asset);
        $node = $record->networkNode;

        return view('network.assets.show', [
            'type' => $nodeType,
            'asset' => $record,
            'photos' => $this->photoService->forNode($node),
            'photoCompliance' => $this->photoService->complianceStatus($node),
            'qrUrl' => $this->qrCodeService->publicUrl($node->qr_code_path),
            'scanUrl' => $this->qrCodeService->scanUrl($node),
        ]);
    }

    public function edit(string $type, int $asset): View
    {
        $nodeType = $this->resolveType($type);
        $record = $this->assetService->find($nodeType, $asset);

        return view('network.assets.form', [
            'type' => $nodeType,
            'asset' => $record,
            'parents' => $this->parentsFor($nodeType),
            'suggestedCode' => null,
            'splitterRatios' => SplitterRatio::cases(),
        ]);
    }

    public function update(UpdateNetworkAssetRequest $request, string $type, int $asset): RedirectResponse
    {
        $nodeType = $this->resolveType($type);
        $record = $this->assetService->find($nodeType, $asset);

        try {
            $this->assetService->update($nodeType, $record, $request->validated());
        } catch (RuntimeException $e) {
            return back()->withInput()->withErrors(['form' => $e->getMessage()]);
        }

        return redirect()
            ->route('network.assets.show', [$nodeType->routeSlug(), $record->id])
            ->with('success', __('hfnms.asset_updated'));
    }

    public function destroy(string $type, int $asset): RedirectResponse
    {
        $this->authorize('network.delete');

        $nodeType = $this->resolveType($type);
        $record = $this->assetService->find($nodeType, $asset);

        try {
            $this->assetService->delete($nodeType, $record);
        } catch (RuntimeException $e) {
            return back()->withErrors(['form' => $e->getMessage()]);
        }

        return redirect()
            ->route('network.assets.index', $nodeType->routeSlug())
            ->with('success', __('hfnms.asset_deleted'));
    }

    private function resolveType(string $slug): NetworkNodeType
    {
        $type = NetworkNodeType::fromRouteSlug($slug);

        if (! $type) {
            abort(404);
        }

        return $type;
    }

    /** @return \Illuminate\Support\Collection<int, \App\Models\NetworkNode> */
    private function parentsFor(NetworkNodeType $type): \Illuminate\Support\Collection
    {
        $parentType = $type->expectedParentType();

        return $parentType
            ? $this->nodeRepository->parentsOfType($parentType)
            : collect();
    }
}
