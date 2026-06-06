<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateSettingsRequest;
use App\Services\Cms\ModuleService;
use App\Services\Cms\SettingsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class SettingsController extends Controller
{
    public function __construct(
        private readonly SettingsService $settingsService,
        private readonly ModuleService $moduleService,
    ) {}

    public function index(): View
    {
        $this->authorize('cms.settings.manage');

        return view('admin.settings.index', [
            'settings' => $this->settingsService->groupedForEdit(),
            'modules' => $this->moduleService->all(),
            'groupLabels' => $this->settingsService->groupLabels(),
        ]);
    }

    public function update(UpdateSettingsRequest $request): RedirectResponse
    {
        $this->authorize('cms.settings.manage');

        $this->settingsService->updateMany($request->validated('settings', []));
        $this->moduleService->syncEnabled($request->validated('modules', []));

        return redirect()
            ->route('settings.index')
            ->with('success', __('hfnms.settings_saved'));
    }
}
