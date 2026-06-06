<?php

namespace App\Services\Cms;

use App\Models\CmsModule;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class ModuleService
{
    private const CACHE_KEY = 'cms.modules';

    public function isEnabled(string $slug): bool
    {
        $modules = $this->all();

        $module = $modules->firstWhere('slug', $slug);

        if (! $module) {
            return true;
        }

        return $module->is_enabled || $module->is_core;
    }

    public function config(string $slug, ?string $key = null, mixed $default = null): mixed
    {
        $module = $this->all()->firstWhere('slug', $slug);

        if (! $module || ! is_array($module->config)) {
            return $default;
        }

        if ($key === null) {
            return $module->config;
        }

        return $module->config[$key] ?? $default;
    }

    public function all(): Collection
    {
        return CmsModule::query()->orderBy('sort_order')->get();
    }

    public function flush(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    /** @param  list<string>  $enabledSlugs */
    public function syncEnabled(array $enabledSlugs): void
    {
        foreach ($this->all() as $module) {
            if ($module->is_core) {
                continue;
            }

            $module->update(['is_enabled' => in_array($module->slug, $enabledSlugs, true)]);
        }

        $this->flush();
    }
}
