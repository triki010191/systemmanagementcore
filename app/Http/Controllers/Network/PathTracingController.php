<?php

namespace App\Http\Controllers\Network;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Services\Network\PathTracingService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

class PathTracingController extends Controller
{
    public function __construct(
        private readonly PathTracingService $pathTracingService,
    ) {}

    public function index(Request $request): View
    {
        $customers = Customer::query()
            ->with('networkNode')
            ->orderBy('code')
            ->limit(20)
            ->get();

        $trace = null;
        $code = $request->string('code')->trim()->toString();

        if ($code !== '') {
            $trace = $this->pathTracingService->traceByCustomerCode($code);
        }

        return view('network.path-tracing.index', [
            'customers' => $customers,
            'trace' => $trace,
            'searchCode' => $code,
        ]);
    }

    public function search(Request $request): JsonResponse
    {
        $query = $request->string('q')->trim()->toString();

        if (strlen($query) < 2) {
            return response()->json([]);
        }

        $results = Customer::query()
            ->with('networkNode')
            ->where(function ($q) use ($query) {
                $q->where('code', 'like', "%{$query}%")
                    ->orWhere('name', 'like', "%{$query}%")
                    ->orWhere('phone', 'like', "%{$query}%");
            })
            ->limit(10)
            ->get()
            ->map(fn (Customer $c) => [
                'id' => $c->id,
                'code' => $c->code,
                'name' => $c->name,
                'status' => $c->status,
                'rx_power_dbm' => $c->rx_power_dbm,
            ]);

        return response()->json($results);
    }

    public function exportPdf(Request $request): Response
    {
        $code = $request->string('code')->trim()->toString();

        abort_if($code === '', 404);

        $trace = $this->pathTracingService->traceByCustomerCode($code);

        abort_if($trace === null, 404);

        $pdf = Pdf::loadView('network.path-tracing.pdf', [
            'trace' => $trace,
            'generatedAt' => now(),
        ])->setPaper('a4', 'portrait');

        $filename = 'path-trace-'.$trace->customer->code.'-'.now()->format('Ymd-His').'.pdf';

        return $pdf->download($filename);
    }
}
