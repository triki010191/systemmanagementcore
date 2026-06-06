<?php

namespace App\Services\Cms;

use App\Models\SystemSetting;
use Illuminate\Support\Facades\Cache;

class SettingsService
{
    private const CACHE_KEY = 'cms.settings';

    private const CACHE_TTL = 3600;

    public function get(string $group, string $key, mixed $default = null): mixed
    {
        $settings = $this->all();

        return $settings[$group][$key] ?? $default;
    }

    public function featureEnabled(string $key, bool $default = true): bool
    {
        $value = $this->get('features', $key, $default);

        return filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }

    public function getPublic(): array
    {
        return SystemSetting::query()
            ->where('is_public', true)
            ->get()
            ->groupBy('group')
            ->map(fn ($items) => $items->mapWithKeys(fn ($s) => [$s->key => $this->castValue($s)]))
            ->toArray();
    }

    public function set(string $group, string $key, mixed $value, string $type = 'string'): SystemSetting
    {
        $setting = SystemSetting::query()->updateOrCreate(
            ['group' => $group, 'key' => $key],
            ['value' => is_array($value) ? json_encode($value) : (string) $value, 'type' => $type],
        );

        Cache::forget(self::CACHE_KEY);

        return $setting;
    }

    public function all(): array
    {
        return Cache::remember(self::CACHE_KEY, self::CACHE_TTL, function () {
            return SystemSetting::query()
                ->orderBy('sort_order')
                ->get()
                ->groupBy('group')
                ->map(fn ($items) => $items->mapWithKeys(fn ($s) => [$s->key => $this->castValue($s)]))
                ->toArray();
        });
    }

    public function flush(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    /** @return array<string, \Illuminate\Support\Collection<int, SystemSetting>> */
    public function groupedForEdit(): array
    {
        return SystemSetting::query()
            ->orderBy('group')
            ->orderBy('sort_order')
            ->get()
            ->groupBy('group')
            ->all();
    }

    /** @return array<string, string> */
    public function groupLabels(): array
    {
        return [
            'branding' => __('hfnms.settings_group_branding'),
            'localization' => __('hfnms.settings_group_localization'),
            'features' => __('hfnms.settings_group_features'),
            'integration' => __('hfnms.settings_group_integration'),
        ];
    }

    /** @param  array<int|string, mixed>  $values */
    public function updateMany(array $values): void
    {
        foreach ($values as $id => $value) {
            $setting = SystemSetting::query()->find($id);

            if (! $setting) {
                continue;
            }

            if ($setting->type === 'boolean') {
                $value = filter_var($value, FILTER_VALIDATE_BOOLEAN) ? '1' : '0';
            }

            if ($setting->type === 'json' && is_string($value) && $value !== '') {
                json_decode($value, true);

                if (json_last_error() !== JSON_ERROR_NONE) {
                    continue;
                }
            }

            $setting->update(['value' => $value === null ? '' : (string) $value]);
        }

        $this->flush();
    }

    private function castValue(SystemSetting $setting): mixed
    {
        return match ($setting->type) {
            'boolean' => filter_var($setting->value, FILTER_VALIDATE_BOOLEAN),
            'integer' => (int) $setting->value,
            'json' => json_decode($setting->value ?? '{}', true),
            default => $setting->value,
        };
    }
}
