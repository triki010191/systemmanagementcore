<?php

namespace App\DTO;

readonly class SorParseResult
{
    /**
     * @param  array<int, array<string, mixed>>  $eventPoints
     */
    public function __construct(
        public bool $success,
        public ?float $totalLossDb = null,
        public ?float $distanceKm = null,
        public ?float $faultDistanceKm = null,
        public array $eventPoints = [],
        public ?string $message = null,
    ) {}

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'success' => $this->success,
            'total_loss_db' => $this->totalLossDb,
            'distance_km' => $this->distanceKm,
            'fault_distance_km' => $this->faultDistanceKm,
            'event_points' => $this->eventPoints,
            'message' => $this->message,
        ];
    }
}
