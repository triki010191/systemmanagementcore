<?php

namespace App\Http\Requests\Concerns;

use Illuminate\Database\Eloquent\Model;

trait ResolvesRouteModelId
{
    protected function routeModelId(string $parameter): int
    {
        $value = $this->route($parameter);

        if ($value instanceof Model) {
            return (int) $value->getKey();
        }

        return (int) $value;
    }
}
