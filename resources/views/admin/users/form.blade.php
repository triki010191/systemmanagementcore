@php
    $isEdit = $user !== null;
    $action = $isEdit ? route('users.update', $user) : route('users.store');
    $selectedRole = old('role', $user?->roles->first()?->name ?? 'teknisi');
@endphp

<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="font-semibold text-headline-md text-on-surface">
                {{ $isEdit ? __('hfnms.edit_user') : __('hfnms.add_user') }}
            </h2>
            <p class="text-body-sm text-on-surface-variant">{{ __('hfnms.user_form_subtitle') }}</p>
        </div>
    </x-slot>

    <div class="max-w-[600px] mx-auto">
        <form method="POST" action="{{ $action }}" class="bg-surface-container-lowest border border-outline-variant rounded-xl p-lg space-y-lg shadow-sm">
            @csrf
            @if ($isEdit) @method('PUT') @endif

            <div>
                <label class="text-label-caps text-on-surface-variant block mb-xs">{{ __('hfnms.name') }} *</label>
                <input type="text" name="name" value="{{ old('name', $user?->name) }}" required
                       class="w-full rounded-lg border-outline-variant text-body-sm">
                @error('name') <p class="text-error text-body-sm mt-xs">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="text-label-caps text-on-surface-variant block mb-xs">{{ __('hfnms.email') }} *</label>
                <input type="email" name="email" value="{{ old('email', $user?->email) }}" required
                       class="w-full rounded-lg border-outline-variant text-body-sm">
                @error('email') <p class="text-error text-body-sm mt-xs">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="text-label-caps text-on-surface-variant block mb-xs">{{ __('hfnms.role') }} *</label>
                <select name="role" required class="w-full rounded-lg border-outline-variant text-body-sm">
                    @foreach ($roles as $role)
                        <option value="{{ $role->name }}" @selected($selectedRole === $role->name)>{{ __('hfnms.role_'.$role->name) }}</option>
                    @endforeach
                </select>
                @error('role') <p class="text-error text-body-sm mt-xs">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="text-label-caps text-on-surface-variant block mb-xs">
                    {{ __('hfnms.password') }} {{ $isEdit ? '('.__('hfnms.password_optional').')' : '*' }}
                </label>
                <input type="password" name="password" {{ $isEdit ? '' : 'required' }}
                       class="w-full rounded-lg border-outline-variant text-body-sm" autocomplete="new-password">
                @error('password') <p class="text-error text-body-sm mt-xs">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="text-label-caps text-on-surface-variant block mb-xs">{{ __('hfnms.password_confirmation') }}</label>
                <input type="password" name="password_confirmation"
                       class="w-full rounded-lg border-outline-variant text-body-sm" autocomplete="new-password">
            </div>

            <div class="flex items-center gap-md pt-sm">
                <button type="submit" class="px-lg py-sm bg-primary text-on-primary rounded font-semibold text-body-sm hover:opacity-90">
                    {{ $isEdit ? __('hfnms.save') : __('hfnms.create_user') }}
                </button>
                <a href="{{ route('users.index') }}" class="text-primary text-body-sm font-semibold hover:underline">{{ __('hfnms.cancel') }}</a>
            </div>
        </form>
    </div>
</x-app-layout>
