<?php

namespace App\DTO;

use App\Models\Customer;

readonly class PathTraceResult
{
    /**
     * @param  array<int, array<string, mixed>>  $hops
     * @param  array<string, mixed>  $metrics
     * @param  array<int, string>  $missingSegments
     */
    public function __construct(
        public string $traceId,
        public Customer $customer,
        public array $hops,
        public array $metrics,
        public bool $isComplete,
        public array $missingSegments = [],
    ) {}
}
