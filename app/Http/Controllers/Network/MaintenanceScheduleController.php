<?php

namespace App\Http\Controllers\Network;

use App\Http\Controllers\Controller;
use App\Http\Requests\Network\StoreMaintenanceScheduleRequest;
use App\Http\Requests\Network\UpdateMaintenanceScheduleRequest;
use App\Models\MaintenanceSchedule;
use App\Models\NetworkNode;
use App\Models\User;
use App\Services\Network\MaintenanceScheduleService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MaintenanceScheduleController extends Controller
{
    public function __construct(
        private readonly MaintenanceScheduleService $scheduleService,
    ) {}

    public function index(Request $request): View
    {
        $schedules = $this->scheduleService->paginate($request->string('status')->toString() ?: null);

        return view('network.maintenance-schedules.index', compact('schedules'));
    }

    public function calendar(Request $request): View
    {
        $monthParam = $request->string('month')->toString();
        $current = $monthParam !== '' && preg_match('/^\d{4}-\d{2}$/', $monthParam)
            ? Carbon::createFromFormat('Y-m', $monthParam)->startOfMonth()
            : now()->startOfMonth();

        $year = (int) $current->format('Y');
        $month = (int) $current->format('m');
        $events = $this->scheduleService->eventsForMonth($year, $month);
        $eventsByDay = $events->groupBy(fn (MaintenanceSchedule $s) => $s->scheduled_at->format('j'));

        $firstDay = Carbon::create($year, $month, 1);
        $daysInMonth = $firstDay->daysInMonth;
        $startWeekday = $firstDay->dayOfWeekIso;
        $prevMonth = $firstDay->copy()->subMonth()->format('Y-m');
        $nextMonth = $firstDay->copy()->addMonth()->format('Y-m');

        return view('network.maintenance-schedules.calendar', [
            'year' => $year,
            'month' => $month,
            'monthLabel' => $firstDay->translatedFormat('F Y'),
            'daysInMonth' => $daysInMonth,
            'startWeekday' => $startWeekday,
            'eventsByDay' => $eventsByDay,
            'prevMonth' => $prevMonth,
            'nextMonth' => $nextMonth,
        ]);
    }

    public function create(): View
    {
        return view('network.maintenance-schedules.form', [
            'schedule' => null,
            'nodes' => $this->nodeOptions(),
            'assignees' => $this->assigneeOptions(),
        ]);
    }

    public function store(StoreMaintenanceScheduleRequest $request): RedirectResponse
    {
        $schedule = $this->scheduleService->create($request->validated());

        return redirect()
            ->route('maintenance-schedules.show', $schedule)
            ->with('success', __('hfnms.maintenance_created'));
    }

    public function show(MaintenanceSchedule $maintenanceSchedule): View
    {
        $schedule = $this->scheduleService->find($maintenanceSchedule->id);

        return view('network.maintenance-schedules.show', compact('schedule'));
    }

    public function edit(MaintenanceSchedule $maintenanceSchedule): View
    {
        $schedule = $this->scheduleService->find($maintenanceSchedule->id);

        return view('network.maintenance-schedules.form', [
            'schedule' => $schedule,
            'nodes' => $this->nodeOptions(),
            'assignees' => $this->assigneeOptions(),
        ]);
    }

    public function update(UpdateMaintenanceScheduleRequest $request, MaintenanceSchedule $maintenanceSchedule): RedirectResponse
    {
        $schedule = $this->scheduleService->update($maintenanceSchedule, $request->validated());

        return redirect()
            ->route('maintenance-schedules.show', $schedule)
            ->with('success', __('hfnms.maintenance_updated'));
    }

    public function destroy(MaintenanceSchedule $maintenanceSchedule): RedirectResponse
    {
        $this->scheduleService->delete($maintenanceSchedule);

        return redirect()
            ->route('maintenance-schedules.index')
            ->with('success', __('hfnms.maintenance_deleted'));
    }

    /** @return \Illuminate\Support\Collection<int, NetworkNode> */
    private function nodeOptions()
    {
        return NetworkNode::query()->orderBy('code')->limit(500)->get(['id', 'code', 'name', 'type']);
    }

    /** @return \Illuminate\Support\Collection<int, User> */
    private function assigneeOptions()
    {
        return User::query()->orderBy('name')->get(['id', 'name', 'email']);
    }
}
