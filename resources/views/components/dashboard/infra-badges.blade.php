@props(['counts' => []])

@php
    $types = [
        'pop' => ['label' => 'POP', 'icon' => 'home_work', 'accent' => 'text-blue-700 bg-blue-50 border-blue-100'],
        'olt' => ['label' => 'OLT', 'icon' => 'router', 'accent' => 'text-indigo-700 bg-indigo-50 border-indigo-100'],
        'otb' => ['label' => 'OTB', 'icon' => 'settings_input_hdmi', 'accent' => 'text-violet-700 bg-violet-50 border-violet-100'],
        'odc' => ['label' => 'ODC', 'icon' => 'storage', 'accent' => 'text-cyan-700 bg-cyan-50 border-cyan-100'],
        'odp' => ['label' => 'ODP', 'icon' => 'lan', 'accent' => 'text-teal-700 bg-teal-50 border-teal-100'],
    ];
    $visibleTypes = collect($types)->filter(fn ($meta, $key) => ($counts[$key] ?? 0) > 0);
@endphp

@if ($visibleTypes->isNotEmpty())
    <section class="bg-white border border-outline-variant/60 rounded-[12px] px-4 py-3 shadow-sm">
        <div class="flex flex-col sm:flex-row sm:items-center gap-3">
            <h3 class="text-[11px] font-semibold uppercase tracking-wide text-on-surface-variant shrink-0">{{ __('hfnms.dashboard_infrastructure') }}</h3>
            <div class="flex flex-wrap gap-2">
                @foreach ($visibleTypes as $key => $meta)
                    <div class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg border {{ $meta['accent'] }}">
                        <span class="material-symbols-outlined text-[14px]">{{ $meta['icon'] }}</span>
                        <span class="text-[11px] font-bold uppercase tracking-wide">{{ $meta['label'] }}</span>
                        <span class="text-sm font-bold font-mono">{{ number_format($counts[$key]) }}</span>
                    </div>
                @endforeach
            </div>
        </div>
    </section>
@endif
