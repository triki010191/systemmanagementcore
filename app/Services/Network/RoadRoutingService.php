<?php

namespace App\Services\Network;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class RoadRoutingService
{
    /**
     * @return list<array{lat: float, lng: float}>|null
     */
    public function routeBetween(float $startLat, float $startLng, float $endLat, float $endLng): ?array
    {
        $baseUrl = rtrim((string) config('hfnms.osrm_url', 'https://router.project-osrm.org'), '/');
        $profile = (string) config('hfnms.osrm_profile', 'driving');
        $coordinates = sprintf('%F,%F;%F,%F', $startLng, $startLat, $endLng, $endLat);
        $url = "{$baseUrl}/route/v1/{$profile}/{$coordinates}";

        try {
            $response = Http::timeout(10)->get($url, [
                'overview' => 'full',
                'geometries' => 'geojson',
                'steps' => 'false',
            ]);

            if (! $response->successful()) {
                Log::warning('OSRM routing HTTP error', ['status' => $response->status()]);

                return null;
            }

            $points = data_get($response->json(), 'routes.0.geometry.coordinates');

            if (! is_array($points) || count($points) < 2) {
                return null;
            }

            return collect($points)
                ->map(fn (array $point) => [
                    'lat' => (float) $point[1],
                    'lng' => (float) $point[0],
                ])
                ->all();
        } catch (\Throwable $exception) {
            Log::warning('OSRM routing failed', ['message' => $exception->getMessage()]);

            return null;
        }
    }

    /**
     * @return list<array{0: float, 1: float}> Leaflet [lat, lng] pairs
     */
    public function leafletPath(float $startLat, float $startLng, float $endLat, float $endLng): array
    {
        $geometry = $this->routeBetween($startLat, $startLng, $endLat, $endLng);

        if ($geometry === null) {
            return [
                [$startLat, $startLng],
                [$endLat, $endLng],
            ];
        }

        return collect($geometry)
            ->map(fn (array $point) => [$point['lat'], $point['lng']])
            ->all();
    }

    /**
     * @param  list<array{0: float, 1: float}>  $waypoints  Leaflet [lat, lng] pairs in order
     * @return list<array{0: float, 1: float}>
     */
    public function leafletPathThrough(array $waypoints): array
    {
        if (count($waypoints) < 2) {
            return $waypoints;
        }

        $merged = [];

        for ($index = 0; $index < count($waypoints) - 1; $index++) {
            $start = $waypoints[$index];
            $end = $waypoints[$index + 1];
            $segment = $this->leafletPath($start[0], $start[1], $end[0], $end[1]);

            if ($index > 0 && $segment !== []) {
                array_shift($segment);
            }

            $merged = array_merge($merged, $segment);
        }

        return $merged !== [] ? $merged : $waypoints;
    }
}
