<?php

namespace App\Http\Controllers\Network;

use App\Enums\CoreStatus;
use App\Http\Controllers\Controller;
use App\Services\Network\CoreManagementService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CoreManagementController extends Controller
{
    public function __construct(
        private readonly CoreManagementService $coreService,
    ) {}

    public function index(Request $request): View
    {
        $this->authorize('core.manage');

        $cores = $this->coreService->paginate(
            $request->integer('cable_id') ?: null,
            $request->string('status')->toString() ?: null,
        );

        return view('network.cores.index', [
            'cores' => $cores,
            'cables' => $this->coreService->cableOptions(),
            'statusCounts' => $this->coreService->statusCounts(),
            'statuses' => CoreStatus::cases(),
            'filters' => [
                'cable_id' => $request->integer('cable_id') ?: '',
                'status' => $request->string('status')->toString(),
            ],
        ]);
    }
}
