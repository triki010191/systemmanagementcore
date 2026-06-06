<?php

namespace App\Http\Requests\Network;

use App\Enums\NetworkNodeType;
use App\Services\Network\NetworkAssetService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateNetworkAssetRequest extends StoreNetworkAssetRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('network.edit') ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $rules = parent::rules();
        $type = NetworkNodeType::fromRouteSlug($this->route('type'));
        $assetId = (int) $this->route('asset');

        if ($type) {
            $asset = app(NetworkAssetService::class)->find($type, $assetId);
            $nodeId = $asset->networkNode->id;

            $rules['code'] = ['nullable', 'string', 'max:50', Rule::unique('network_nodes', 'code')->ignore($nodeId)];
        }

        return $rules;
    }
}
