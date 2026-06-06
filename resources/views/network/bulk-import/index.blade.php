<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="font-semibold text-headline-md text-on-surface">{{ __('hfnms.bulk_import') }}</h2>
            <p class="text-body-sm text-on-surface-variant">{{ __('hfnms.bulk_import_subtitle') }}</p>
        </div>
    </x-slot>

    <div class="max-w-[1000px] mx-auto space-y-lg">
        @if (session('success'))
            <div class="px-md py-sm bg-green-50 border border-green-200 text-success rounded-lg text-body-sm">{{ session('success') }}</div>
        @endif

        @if (session('import_errors') && count(session('import_errors')))
            <div class="px-md py-sm bg-red-50 border border-red-200 rounded-lg text-body-sm">
                <p class="font-semibold text-error mb-sm">{{ __('hfnms.import_errors') }}:</p>
                <ul class="list-disc pl-lg space-y-xs text-error">
                    @foreach (session('import_errors') as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="grid grid-cols-1 md:grid-cols-2 gap-lg">
            {{-- ODP Import --}}
            <div class="bg-surface-container-lowest border border-outline-variant rounded-lg p-lg">
                <div class="flex items-center gap-sm mb-md">
                    <span class="material-symbols-outlined text-primary">lan</span>
                    <h3 class="font-semibold text-body-md">ODP</h3>
                </div>
                <p class="text-body-sm text-on-surface-variant mb-md">{{ __('hfnms.import_odp_help') }}</p>
                <a href="{{ route('bulk-import.template', 'odp') }}" class="inline-flex items-center gap-xs text-primary text-body-sm font-semibold hover:underline mb-lg">
                    <span class="material-symbols-outlined text-[18px]">download</span>
                    {{ __('hfnms.download_template') }}
                </a>
                <form method="POST" action="{{ route('bulk-import.import', 'odp') }}" enctype="multipart/form-data" class="space-y-md">
                    @csrf
                    <input type="file" name="file" accept=".xlsx,.xls,.csv" required class="w-full text-body-sm">
                    <button type="submit" class="w-full px-md py-sm bg-primary text-on-primary rounded font-semibold text-body-sm hover:opacity-90">
                        {{ __('hfnms.import_odp') }}
                    </button>
                </form>
            </div>

            {{-- Customer Import --}}
            <div class="bg-surface-container-lowest border border-outline-variant rounded-lg p-lg">
                <div class="flex items-center gap-sm mb-md">
                    <span class="material-symbols-outlined text-primary">group</span>
                    <h3 class="font-semibold text-body-md">{{ __('hfnms.customer') }}</h3>
                </div>
                <p class="text-body-sm text-on-surface-variant mb-md">{{ __('hfnms.import_customer_help') }}</p>
                <a href="{{ route('bulk-import.template', 'customer') }}" class="inline-flex items-center gap-xs text-primary text-body-sm font-semibold hover:underline mb-lg">
                    <span class="material-symbols-outlined text-[18px]">download</span>
                    {{ __('hfnms.download_template') }}
                </a>
                <form method="POST" action="{{ route('bulk-import.import', 'customer') }}" enctype="multipart/form-data" class="space-y-md">
                    @csrf
                    <input type="file" name="file" accept=".xlsx,.xls,.csv" required class="w-full text-body-sm">
                    <button type="submit" class="w-full px-md py-sm bg-primary text-on-primary rounded font-semibold text-body-sm hover:opacity-90">
                        {{ __('hfnms.import_customers') }}
                    </button>
                </form>
            </div>
        </div>

        <div class="bg-surface-container-low border border-outline-variant rounded-lg p-lg text-body-sm text-on-surface-variant">
            <h4 class="font-semibold text-on-surface mb-sm">{{ __('hfnms.import_notes_title') }}</h4>
            <ul class="list-disc pl-lg space-y-xs">
                <li>{{ __('hfnms.import_note_format') }}</li>
                <li>{{ __('hfnms.import_note_gps') }}</li>
                <li>{{ __('hfnms.import_note_auto_code') }}</li>
                <li>{{ __('hfnms.import_note_customer_connection') }}</li>
            </ul>
        </div>
    </div>
</x-app-layout>
