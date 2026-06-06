<?php

namespace App\Http\Controllers\Network;

use App\Http\Controllers\Controller;
use App\Http\Requests\Network\StoreTroubleTicketRequest;
use App\Http\Requests\Network\UpdateTroubleTicketRequest;
use App\Models\Customer;
use App\Models\NetworkNode;
use App\Models\TroubleTicket;
use App\Models\User;
use App\Services\Network\TroubleTicketService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TroubleTicketController extends Controller
{
    public function __construct(
        private readonly TroubleTicketService $ticketService,
    ) {}

    public function index(Request $request): View
    {
        $tickets = $this->ticketService->paginate($request->string('status')->toString() ?: null);

        return view('network.trouble-tickets.index', compact('tickets'));
    }

    public function create(): View
    {
        return view('network.trouble-tickets.form', [
            'ticket' => null,
            'nodes' => $this->nodeOptions(),
            'customers' => $this->customerOptions(),
            'assignees' => $this->assigneeOptions(),
        ]);
    }

    public function store(StoreTroubleTicketRequest $request): RedirectResponse
    {
        $ticket = $this->ticketService->create($request->validated());

        return redirect()
            ->route('trouble-tickets.show', $ticket)
            ->with('success', __('hfnms.ticket_created'));
    }

    public function show(TroubleTicket $troubleTicket): View
    {
        $ticket = $this->ticketService->find($troubleTicket->id);

        return view('network.trouble-tickets.show', compact('ticket'));
    }

    public function edit(TroubleTicket $troubleTicket): View
    {
        $ticket = $this->ticketService->find($troubleTicket->id);

        return view('network.trouble-tickets.form', [
            'ticket' => $ticket,
            'nodes' => $this->nodeOptions(),
            'customers' => $this->customerOptions(),
            'assignees' => $this->assigneeOptions(),
        ]);
    }

    public function update(UpdateTroubleTicketRequest $request, TroubleTicket $troubleTicket): RedirectResponse
    {
        $ticket = $this->ticketService->update($troubleTicket, $request->validated());

        return redirect()
            ->route('trouble-tickets.show', $ticket)
            ->with('success', __('hfnms.ticket_updated'));
    }

    public function destroy(TroubleTicket $troubleTicket): RedirectResponse
    {
        $this->ticketService->delete($troubleTicket);

        return redirect()
            ->route('trouble-tickets.index')
            ->with('success', __('hfnms.ticket_deleted'));
    }

    /** @return \Illuminate\Support\Collection<int, NetworkNode> */
    private function nodeOptions()
    {
        return NetworkNode::query()
            ->orderBy('code')
            ->limit(500)
            ->get(['id', 'code', 'name', 'type']);
    }

    /** @return \Illuminate\Support\Collection<int, Customer> */
    private function customerOptions()
    {
        return Customer::query()
            ->orderBy('code')
            ->limit(500)
            ->get(['id', 'code', 'name']);
    }

    /** @return \Illuminate\Support\Collection<int, User> */
    private function assigneeOptions()
    {
        return User::query()
            ->orderBy('name')
            ->get(['id', 'name', 'email']);
    }
}
