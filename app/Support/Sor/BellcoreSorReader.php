<?php

namespace App\Support\Sor;

/**
 * Bellcore/Telcordia SR-4731 binary .sor parser (format 2, pyOTDR-compatible).
 */
class BellcoreSorReader
{
    private const SPEED_OF_LIGHT_KM_PER_USEC = 0.299792458;

    /** @var array<string, array{pos: int, size: int}> */
    private array $blocks = [];

    private int $format = 2;

    private float $groupIndex = 1.468;

    /** @return array{total_loss_db: ?float, distance_km: ?float, fault_distance_km: ?float, event_points: array<int, array<string, mixed>>}|null */
    public function parse(string $content): ?array
    {
        if ($content === '' || ! str_contains($content, 'FxdParams') && ! str_contains($content, 'KeyEvents')) {
            return null;
        }

        $reader = new BellcoreBlockReader($content);

        if (! $this->parseMap($reader)) {
            return null;
        }

        $this->parseFxdParams($content);

        if (! isset($this->blocks['KeyEvents'])) {
            return null;
        }

        return $this->parseKeyEvents($content);
    }

    private function parseMap(BellcoreBlockReader $reader): bool
    {
        $reader->seek(0);
        $marker = $reader->readString();

        if ($marker === 'Map') {
            $this->format = 2;
        } else {
            $this->format = 1;
            $reader->seek(0);
        }

        $reader->readUint(2);
        $mapSize = $reader->readUint(4);
        $blockCount = $reader->readUint(2) - 1;

        if ($blockCount < 1 || $mapSize > $reader->length()) {
            return false;
        }

        $startPos = $mapSize;

        for ($i = 0; $i < $blockCount; $i++) {
            $name = $reader->readString();
            $reader->readUint(2);
            $size = $reader->readUint(4);

            if ($name !== '') {
                $this->blocks[$name] = ['pos' => $startPos, 'size' => $size];
            }

            $startPos += $size;
        }

        return $this->blocks !== [];
    }

    private function parseFxdParams(string $content): void
    {
        if (! isset($this->blocks['FxdParams'])) {
            return;
        }

        $reader = new BellcoreBlockReader($content);
        $reader->seek($this->blocks['FxdParams']['pos']);

        if ($this->format === 2) {
            $header = $reader->readString();

            if ($header !== 'FxdParams') {
                return;
            }
        }

        $reader->readUint(4);
        $reader->readBytes(2);
        $reader->readUint(2);
        $reader->readSigned(4);
        $reader->readSigned(4);

        if ($this->format === 2) {
            $reader->readUint(2);
            $reader->readUint(2);
            $reader->readUint(4);
            $reader->readUint(4);
            $indexRaw = $reader->readUint(4);
        } else {
            $reader->readUint(2);
            $reader->readUint(2);
            $reader->readUint(4);
            $indexRaw = $reader->readUint(4);
        }

        $this->groupIndex = max(1.0, $indexRaw * 1e-5);
    }

    /** @return array{total_loss_db: ?float, distance_km: ?float, fault_distance_km: ?float, event_points: array<int, array<string, mixed>>} */
    private function parseKeyEvents(string $content): array
    {
        $reader = new BellcoreBlockReader($content);
        $reader->seek($this->blocks['KeyEvents']['pos']);

        if ($this->format === 2) {
            $header = $reader->readString();

            if ($header !== 'KeyEvents') {
                return $this->emptyResult();
            }
        }

        $factor = 1e-4 * self::SPEED_OF_LIGHT_KM_PER_USEC / $this->groupIndex;
        $eventCount = $reader->readUint(2);
        $events = [];

        for ($i = 0; $i < $eventCount; $i++) {
            $eventNumber = $reader->readUint(2);
            $distanceKm = round($reader->readUint(4) * $factor, 3);
            $slope = round($reader->readSigned(2) * 0.001, 3);
            $spliceLoss = round($reader->readSigned(2) * 0.001, 3);
            $reflectLoss = round($reader->readSigned(4) * 0.001, 3);
            $reader->readBytes(8);

            if ($this->format === 2) {
                $reader->readUint(4);
                $reader->readUint(4);
                $reader->readUint(4);
                $reader->readUint(4);
                $reader->readUint(4);
                $reader->readString();
            }

            $lossDb = abs($spliceLoss) >= abs($reflectLoss) ? $spliceLoss : $reflectLoss;

            $events[] = [
                'index' => $eventNumber ?: ($i + 1),
                'distance_km' => $distanceKm,
                'loss_db' => $lossDb,
                'slope_db_km' => $slope,
            ];
        }

        $totalLoss = round($reader->readSigned(4) * 0.001, 3);
        $lossStart = round($reader->readSigned(4) * $factor, 3);
        $lossEnd = round($reader->readUint(4) * $factor, 3);

        $distanceKm = $lossEnd > 0 ? $lossEnd : ($events !== [] ? end($events)['distance_km'] : null);

        $faultDistance = null;

        foreach ($events as $event) {
            if (abs($event['loss_db']) >= 0.1) {
                $faultDistance = $event['distance_km'];
                break;
            }
        }

        if ($faultDistance === null && $events !== []) {
            $faultDistance = $events[0]['distance_km'];
        }

        return [
            'total_loss_db' => $totalLoss > 0 ? $totalLoss : null,
            'distance_km' => $distanceKm,
            'fault_distance_km' => $faultDistance,
            'event_points' => $events,
        ];
    }

    /** @return array{total_loss_db: null, distance_km: null, fault_distance_km: null, event_points: array{}} */
    private function emptyResult(): array
    {
        return [
            'total_loss_db' => null,
            'distance_km' => null,
            'fault_distance_km' => null,
            'event_points' => [],
        ];
    }
}
