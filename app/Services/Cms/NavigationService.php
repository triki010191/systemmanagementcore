<?php

namespace App\Services\Cms;

use App\Models\NavigationMenu;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

class NavigationService
{
    public function __construct(
        private readonly ModuleService $moduleService,
        private readonly SettingsService $settingsService,
    ) {}

    public function forLocation(string $location = 'sidebar'): Collection
    {
        $menu = NavigationMenu::query()
            ->where('location', $location)
            ->where('is_active', true)
            ->with(['allItems' => fn ($q) => $q->where('is_active', true)->orderBy('sort_order')])
            ->first();

        if (! $menu) {
            return collect();
        }

        return $this->filterItems($menu->allItems->whereNull('parent_id'));
    }

    private function filterItems(Collection $items): Collection
    {
        return $items
            ->filter(fn ($item) => $this->canSee($item))
            ->map(function ($item) {
                $item->setRelation(
                    'children',
                    $this->filterItems($item->children),
                );

                return $item;
            })
            ->values();
    }

    private function canSee($item): bool
    {
        if ($item->module_slug && ! $this->moduleService->isEnabled($item->module_slug)) {
            return false;
        }

        if ($item->url === '/bulk-import' && ! $this->settingsService->featureEnabled('bulk_import_enabled')) {
            return false;
        }

        if ($item->permission && Auth::check() && ! Auth::user()->can($item->permission)) {
            return false;
        }

        if ($item->permission && ! Auth::check()) {
            return false;
        }

        return true;
    }
}
