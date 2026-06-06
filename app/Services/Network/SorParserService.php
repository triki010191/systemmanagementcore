<?php

namespace App\Services\Network;

use App\DTO\SorParseResult;
use App\Support\Sor\BellcoreSorReader;

class SorParserService
{
    public function __construct(
        private readonly BellcoreSorReader $bellcoreReader,
    ) {}

    public function parse(string $filePath): SorParseResult
    {
        if (! is_readable($filePath)) {
            return new SorParseResult(false, message: __('hfnms.sor_parse_unreadable'));
        }

        $content = file_get_contents($filePath);

        if ($content === false || $content === '') {
            return new SorParseResult(false, message: __('hfnms.sor_parse_empty'));
        }

        $ascii = $this->parseAsciiLayer($content);
        $bellcore = $this->bellcoreReader->parse($content);

        $totalLoss = $bellcore['total_loss_db'] ?? $ascii['total_loss_db'];
        $distanceKm = $bellcore['distance_km'] ?? $ascii['distance_km'];
        $eventPoints = $bellcore['event_points'] ?? [];
        $faultDistanceKm = $bellcore['fault_distance_km'] ?? $ascii['fault_distance_km'];

        if ($eventPoints === [] && $ascii['event_points'] !== []) {
            $eventPoints = $ascii['event_points'];
            $faultDistanceKm ??= $ascii['fault_distance_km'];
        }

        $parseMethod = $bellcore !== null && ($bellcore['total_loss_db'] !== null || $bellcore['event_points'] !== [])
            ? 'bellcore'
            : ($ascii['has_metrics'] ? 'ascii' : null);

        $success = $totalLoss !== null || $distanceKm !== null || $faultDistanceKm !== null;

        $message = match (true) {
            ! $success => __('hfnms.sor_parse_no_metrics'),
            $parseMethod === 'bellcore' => __('hfnms.sor_parse_success_bellcore'),
            default => __('hfnms.sor_parse_success'),
        };

        return new SorParseResult(
            success: $success,
            totalLossDb: $totalLoss,
            distanceKm: $distanceKm,
            faultDistanceKm: $faultDistanceKm,
            eventPoints: $eventPoints,
            message: $message,
        );
    }

    /** @return array{total_loss_db: ?float, distance_km: ?float, fault_distance_km: ?float, event_points: array<int, array<string, mixed>>, has_metrics: bool} */
    private function parseAsciiLayer(string $content): array
    {
        $ascii = $this->extractAscii($content);

        $totalLoss = $this->matchFirstFloat($ascii, [
            '/total\s*loss[:\s=]*(-?\d+\.?\d*)/i',
            '/link\s*loss[:\s=]*(-?\d+\.?\d*)/i',
            '/loss[:\s=]*(-?\d+\.?\d*)\s*dB/i',
        ]);

        $distanceKm = $this->matchFirstFloat($ascii, [
            '/fiber\s*length[:\s=]*(\d+\.?\d*)\s*km/i',
            '/link\s*length[:\s=]*(\d+\.?\d*)\s*km/i',
            '/test\s*range[:\s=]*(\d+\.?\d*)\s*km/i',
            '/range[:\s=]*(\d+\.?\d*)\s*km/i',
        ]);

        if ($distanceKm === null) {
            $distanceM = $this->matchFirstFloat($ascii, [
                '/fiber\s*length[:\s=]*(\d+\.?\d*)\s*m/i',
                '/link\s*length[:\s=]*(\d+\.?\d*)\s*m/i',
            ]);

            if ($distanceM !== null) {
                $distanceKm = round($distanceM / 1000, 3);
            }
        }

        if ($distanceKm === null) {
            $distanceKm = $this->matchFirstFloat($ascii, [
                '/acqRange[^\d]*(\d+\.?\d*)/i',
            ]);

            if ($distanceKm !== null && $distanceKm > 100) {
                $distanceKm = round($distanceKm / 1000, 3);
            }
        }

        [$faultDistanceKm, $eventPoints] = $this->parseAsciiEvents($ascii);

        $hasMetrics = $totalLoss !== null || $distanceKm !== null || $faultDistanceKm !== null;

        return [
            'total_loss_db' => $totalLoss,
            'distance_km' => $distanceKm,
            'fault_distance_km' => $faultDistanceKm,
            'event_points' => $eventPoints,
            'has_metrics' => $hasMetrics,
        ];
    }

    private function extractAscii(string $content): string
    {
        $chunks = [];

        if (preg_match_all('/[\x20-\x7E\r\n\t]{4,}/', $content, $matches)) {
            $chunks = array_merge($chunks, $matches[0]);
        }

        return implode("\n", $chunks);
    }

    /** @param  list<string>  $patterns */
    private function matchFirstFloat(string $haystack, array $patterns): ?float
    {
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $haystack, $m)) {
                return round((float) $m[1], 3);
            }
        }

        return null;
    }

    /** @return array{0: ?float, 1: array<int, array<string, mixed>>} */
    private function parseAsciiEvents(string $ascii): array
    {
        $events = [];

        if (preg_match_all('/event\s*#?\d*\s*[@at\s]*(\d+\.?\d*)\s*km.*?(-?\d+\.?\d*)\s*dB/i', $ascii, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $index => $match) {
                $events[] = [
                    'index' => $index + 1,
                    'distance_km' => round((float) $match[1], 3),
                    'loss_db' => round((float) $match[2], 3),
                ];
            }
        }

        if ($events === [] && preg_match_all('/@?\s*(\d+\.?\d*)\s*km[^0-9-]*(-?\d+\.?\d*)\s*dB/i', $ascii, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $index => $match) {
                $events[] = [
                    'index' => $index + 1,
                    'distance_km' => round((float) $match[1], 3),
                    'loss_db' => round((float) $match[2], 3),
                ];
            }
        }

        $faultDistance = null;

        foreach ($events as $event) {
            if (($event['loss_db'] ?? 0) >= 0.1) {
                $faultDistance = $event['distance_km'];
                break;
            }
        }

        if ($faultDistance === null && $events !== []) {
            $faultDistance = $events[0]['distance_km'] ?? null;
        }

        return [$faultDistance, $events];
    }
}
