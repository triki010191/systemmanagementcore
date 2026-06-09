<?php

namespace App\Http\Controllers\Network;

use App\Http\Controllers\Controller;
use App\Services\Network\GisMapService;
use App\Services\Network\RoadRoutingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class GisMapController extends Controller
{
    public function index(GisMapService $gisMapService): View
    {
        $mapData = $gisMapService->getMapPayload();

        return view('network.gis.index', [
            'mapData' => $mapData,
        ]);
    }

    public function route(Request $request, RoadRoutingService $roadRouting): JsonResponse
    {
        if ($request->has('waypoints')) {
            $validated = $request->validate([
                'waypoints' => ['required', 'array', 'min:2'],
                'waypoints.*.lat' => ['required', 'numeric', 'between:-90,90'],
                'waypoints.*.lng' => ['required', 'numeric', 'between:-180,180'],
            ]);

            $waypoints = collect($validated['waypoints'])
                ->map(fn (array $point) => [(float) $point['lat'], (float) $point['lng']])
                ->all();

            return response()->json(['path' => $roadRouting->leafletPathThrough($waypoints)]);
        }

        $validated = $request->validate([
            'start_lat' => ['required', 'numeric', 'between:-90,90'],
            'start_lng' => ['required', 'numeric', 'between:-180,180'],
            'end_lat' => ['required', 'numeric', 'between:-90,90'],
            'end_lng' => ['required', 'numeric', 'between:-180,180'],
        ]);

        $path = $roadRouting->leafletPath(
            (float) $validated['start_lat'],
            (float) $validated['start_lng'],
            (float) $validated['end_lat'],
            (float) $validated['end_lng'],
        );

        return response()->json(['path' => $path]);
    }
}
