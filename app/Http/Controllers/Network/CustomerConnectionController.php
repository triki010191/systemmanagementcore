<?php

namespace App\Http\Controllers\Network;

use App\Http\Controllers\Controller;
use App\Http\Requests\Network\StoreCustomerConnectionRequest;
use App\Http\Requests\Network\UpdateCustomerConnectionRequest;
use App\Models\Customer;
use App\Models\CustomerConnection;
use App\Models\Odp;
use App\Enums\NetworkNodeType;
use App\Services\Network\CustomerConnectionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use RuntimeException;

class CustomerConnectionController extends Controller
{
    public function __construct(
        private readonly CustomerConnectionService $connectionService,
    ) {}

    public function index(): View
    {
        $connections = $this->connectionService->paginate();

        return view('network.connections.index', compact('connections'));
    }

    public function create(Request $request): View
    {
        $preselectedCustomer = null;
        $suggestedOdpId = null;
        $odpOptions = null;

        if ($request->filled('customer_id')) {
            $preselectedCustomer = Customer::query()
                ->with('networkNode.parent')
                ->find($request->integer('customer_id'));

            if ($preselectedCustomer?->networkNode?->parent?->type === NetworkNodeType::Odp) {
                $suggestedOdpId = Odp::query()
                    ->where('network_node_id', $preselectedCustomer->networkNode->parent_id)
                    ->value('id');
            }
        }

        if ($suggestedOdpId) {
            $odpOptions = $this->connectionService->optionsForOdp($suggestedOdpId);
        }

        return view('network.connections.form', [
            'connection' => null,
            'customers' => $this->connectionService->customersWithoutConnection(),
            'odps' => $this->connectionService->availableOdps(),
            'preselectedCustomer' => $preselectedCustomer,
            'suggestedOdpId' => $suggestedOdpId,
            'odpOptions' => $odpOptions,
        ]);
    }

    public function store(StoreCustomerConnectionRequest $request): RedirectResponse
    {
        try {
            $connection = $this->connectionService->create($request->validated());
        } catch (RuntimeException $e) {
            return back()->withInput()->withErrors(['form' => $e->getMessage()]);
        }

        return redirect()
            ->route('customer-connections.show', $connection)
            ->with('success', __('hfnms.connection_created'));
    }

    public function show(CustomerConnection $connection): View
    {
        $connection = $this->connectionService->find($connection->id);
        $pathComplete = $this->connectionService->pathIsComplete($connection);

        return view('network.connections.show', compact('connection', 'pathComplete'));
    }

    public function edit(CustomerConnection $connection): View
    {
        $connection = $this->connectionService->find($connection->id);
        $odpOptions = $this->connectionService->optionsForOdp($connection->odp_id, $connection->id);

        return view('network.connections.form', [
            'connection' => $connection,
            'customers' => collect([$connection->customer]),
            'odps' => $this->connectionService->availableOdps(),
            'preselectedCustomer' => $connection->customer,
            'odpOptions' => $odpOptions,
        ]);
    }

    public function update(UpdateCustomerConnectionRequest $request, CustomerConnection $connection): RedirectResponse
    {
        try {
            $this->connectionService->update($connection, $request->validated());
        } catch (RuntimeException $e) {
            return back()->withInput()->withErrors(['form' => $e->getMessage()]);
        }

        return redirect()
            ->route('customer-connections.show', $connection)
            ->with('success', __('hfnms.connection_updated'));
    }

    public function destroy(CustomerConnection $connection): RedirectResponse
    {
        $this->connectionService->delete($connection);

        return redirect()
            ->route('customer-connections.index')
            ->with('success', __('hfnms.connection_deleted'));
    }

    public function odpOptions(Request $request, int $odp): JsonResponse
    {
        $except = $request->integer('except') ?: null;

        return response()->json($this->connectionService->optionsForOdp($odp, $except));
    }
}
