<?php

namespace App\Http\Controllers\Network;

use App\Http\Controllers\Controller;
use App\Services\Network\GisMapService;
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
}
