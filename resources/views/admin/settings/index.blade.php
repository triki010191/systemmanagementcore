<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="font-semibold text-headline-md text-on-surface">{{ __('hfnms.settings') }}</h2>
            <p class="text-body-sm text-on-surface-variant">{{ __('hfnms.settings_subtitle') }}</p>
        </div>
    </x-slot>

    <div class="max-w-[900px] mx-auto">
        @if (session('success'))
            <div class="mb-md px-md py-sm bg-green-50 border border-green-200 text-success rounded-lg text-body-sm">
                {{ session('success') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="mb-md px-md py-sm bg-red-50 border border-red-200 text-error rounded-lg text-body-sm">
                <ul class="list-disc pl-lg space-y-xs">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('settings.update') }}" class="space-y-lg">
            @csrf
            @method('PUT')

            @foreach ($settings as $group => $items)
                <section class="bg-surface-container-lowest border border-outline-variant rounded-lg p-lg">
                    <h3 class="text-label-caps text-on-surface-variant mb-lg">
                        {{ $groupLabels[$group] ?? ucfirst($group) }}
                    </h3>

                    <div class="space-y-md">
                        @foreach ($items as $setting)
                            <div>
                                <label class="block text-body-sm font-semibold text-on-surface mb-xs" for="setting_{{ $setting->id }}">
                                    {{ $setting->label ?? $setting->key }}
                                </label>

                                @if ($setting->description)
                                    <p class="text-[11px] text-on-surface-variant mb-xs">{{ $setting->description }}</p>
                                @endif

                                @switch($setting->type)
                                    @case('boolean')
                                        <input type="hidden" name="settings[{{ $setting->id }}]" value="0">
                                        <label class="inline-flex items-center gap-sm cursor-pointer">
                                            <input type="checkbox" id="setting_{{ $setting->id }}" name="settings[{{ $setting->id }}]" value="1"
                                                   @checked(filter_var($setting->value, FILTER_VALIDATE_BOOLEAN))
                                                   class="rounded border-outline-variant text-primary">
                                            <span class="text-body-sm text-on-surface-variant">{{ __('hfnms.enabled') }}</span>
                                        </label>
                                        @break

                                    @case('text')
                                    @case('json')
                                        <textarea id="setting_{{ $setting->id }}" name="settings[{{ $setting->id }}]" rows="{{ $setting->type === 'json' ? 3 : 4 }}"
                                                  class="w-full rounded-lg border-outline-variant text-body-sm font-mono">{{ old('settings.'.$setting->id, $setting->value) }}</textarea>
                                        @break

                                    @default
                                        <input type="{{ $setting->type === 'integer' ? 'number' : 'text' }}"
                                               id="setting_{{ $setting->id }}"
                                               name="settings[{{ $setting->id }}]"
                                               value="{{ old('settings.'.$setting->id, $setting->value) }}"
                                               class="w-full rounded-lg border-outline-variant text-body-sm"
                                               @if ($setting->key === 'primary_color') placeholder="#004cca" @endif>
                                @endswitch
                            </div>
                        @endforeach
                    </div>
                </section>
            @endforeach

            <section class="bg-surface-container-lowest border border-outline-variant rounded-lg p-lg">
                <h3 class="text-label-caps text-on-surface-variant mb-md">{{ __('hfnms.active_modules') }}</h3>
                <p class="text-body-sm text-on-surface-variant mb-lg">{{ __('hfnms.active_modules_hint') }}</p>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-sm">
                    @foreach ($modules as $module)
                        @if ($module->is_core)
                            <div class="flex items-center gap-sm px-md py-sm bg-surface-container-low rounded-lg opacity-70">
                                <span class="material-symbols-outlined text-[18px] text-on-surface-variant">lock</span>
                                <div>
                                    <p class="text-body-sm font-semibold">{{ $module->name }}</p>
                                    <p class="text-[11px] text-on-surface-variant">{{ __('hfnms.core_module') }}</p>
                                </div>
                            </div>
                        @else
                            <label class="flex items-center gap-sm px-md py-sm border border-outline-variant rounded-lg cursor-pointer hover:bg-surface-container-low/50">
                                <input type="checkbox" name="modules[]" value="{{ $module->slug }}"
                                       @checked($module->is_enabled)
                                       class="rounded border-outline-variant text-primary">
                                <div>
                                    <p class="text-body-sm font-semibold">{{ $module->name }}</p>
                                    <p class="text-[11px] text-on-surface-variant font-mono">{{ $module->slug }}</p>
                                </div>
                            </label>
                        @endif
                    @endforeach
                </div>
            </section>

            <div class="flex justify-end">
                <button type="submit" class="px-lg py-sm bg-primary text-on-primary rounded font-semibold text-body-sm hover:opacity-90">
                    {{ __('hfnms.save_settings') }}
                </button>
            </div>
        </form>
    </div>
</x-app-layout>
