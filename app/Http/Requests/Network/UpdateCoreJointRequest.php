<?php

namespace App\Http\Requests\Network;

use App\Http\Requests\Concerns\ResolvesRouteModelId;
use Illuminate\Validation\Rule;

class UpdateCoreJointRequest extends StoreCoreJointRequest
{
    use ResolvesRouteModelId;

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $rules = parent::rules();
        $jointId = $this->routeModelId('coreJoint');

        $rules['code'] = ['required', 'string', 'max:50', Rule::unique('core_joints', 'code')->ignore($jointId)];

        return $rules;
    }
}
