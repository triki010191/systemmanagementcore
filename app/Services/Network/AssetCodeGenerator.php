<?php

namespace App\Services\Network;

use App\Enums\NetworkNodeType;
use App\Models\Customer;
use App\Models\NetworkNode;
use App\Models\Odc;
use App\Models\Odp;
use App\Models\Otb;

class AssetCodeGenerator
{
    public function next(NetworkNodeType $type): string
    {
        return match ($type) {
            NetworkNodeType::Otb => $this->nextPrefixed('OTB-', Otb::class, 'code'),
            NetworkNodeType::Odc => $this->nextPrefixed('ODC-', Odc::class, 'code'),
            NetworkNodeType::Odp => $this->nextPrefixed('ODP-', Odp::class, 'code'),
            NetworkNodeType::Customer => $this->nextCustomerCode(),
            default => throw new \InvalidArgumentException("Auto code not supported for {$type->value}"),
        };
    }

    /** @param class-string<\Illuminate\Database\Eloquent\Model> $modelClass */
    private function nextPrefixed(string $prefix, string $modelClass, string $column): string
    {
        $latest = $modelClass::query()
            ->where($column, 'like', $prefix.'%')
            ->orderByRaw("CAST(SUBSTRING({$column}, ".(strlen($prefix) + 1).") AS UNSIGNED) DESC")
            ->value($column);

        $next = 1;
        if ($latest && preg_match('/'.preg_quote($prefix, '/').'(\d+)/', $latest, $m)) {
            $next = (int) $m[1] + 1;
        }

        return $prefix.str_pad((string) $next, 3, '0', STR_PAD_LEFT);
    }

    private function nextCustomerCode(): string
    {
        $latest = Customer::query()
            ->where('code', 'like', 'HNT%')
            ->orderByRaw('CAST(SUBSTRING(code, 4) AS UNSIGNED) DESC')
            ->value('code');

        $next = 1;
        if ($latest && preg_match('/HNT(\d+)/', $latest, $m)) {
            $next = (int) $m[1] + 1;
        }

        return 'HNT'.str_pad((string) $next, 6, '0', STR_PAD_LEFT);
    }

    public function isUnique(string $code, ?int $exceptNodeId = null): bool
    {
        $query = NetworkNode::query()->where('code', $code);

        if ($exceptNodeId) {
            $query->where('id', '!=', $exceptNodeId);
        }

        return ! $query->exists();
    }
}
