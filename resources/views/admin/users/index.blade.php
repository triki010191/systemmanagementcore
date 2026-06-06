<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between flex-wrap gap-md w-full">
            <div>
                <h2 class="font-semibold text-headline-md text-on-surface">{{ __('hfnms.users') }}</h2>
                <p class="text-body-sm text-on-surface-variant">{{ __('hfnms.users_subtitle') }}</p>
            </div>
            <a href="{{ route('users.create') }}"
               class="inline-flex items-center gap-xs px-md py-sm bg-primary text-on-primary rounded font-semibold text-body-sm hover:opacity-90">
                <span class="material-symbols-outlined text-[18px]">person_add</span>
                {{ __('hfnms.add_user') }}
            </a>
        </div>
    </x-slot>

    <div class="max-w-[1200px] mx-auto space-y-md">
        @if (session('success'))
            <div class="px-md py-sm bg-green-50 border border-green-200 text-success rounded-lg text-body-sm">{{ session('success') }}</div>
        @endif

        <div class="bg-blue-50 border border-blue-200 rounded-xl p-lg text-body-sm">
            <h3 class="font-semibold text-on-surface mb-sm flex items-center gap-sm">
                <span class="material-symbols-outlined text-blue-600 text-[20px]">info</span>
                {{ __('hfnms.sample_users_title') }}
            </h3>
            <p class="text-on-surface-variant mb-md">{{ __('hfnms.sample_users_hint') }}</p>
            <div class="overflow-x-auto">
                <table class="w-full text-body-sm bg-white/70 rounded-lg">
                    <thead>
                        <tr class="text-left text-on-surface-variant text-[11px] uppercase">
                            <th class="px-md py-sm">{{ __('hfnms.role') }}</th>
                            <th class="px-md py-sm">{{ __('hfnms.email') }}</th>
                            <th class="px-md py-sm">{{ __('hfnms.password') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ([
                            ['super-admin', 'admin@hinet.local'],
                            ['noc', 'noc@hinet.local'],
                            ['teknisi', 'teknisi@hinet.local'],
                            ['customer-service', 'cs@hinet.local'],
                            ['manager', 'manager@hinet.local'],
                        ] as [$roleKey, $email])
                            <tr class="border-t border-blue-100">
                                <td class="px-md py-sm font-semibold">{{ __('hfnms.role_'.$roleKey) }}</td>
                                <td class="px-md py-sm font-mono">{{ $email }}</td>
                                <td class="px-md py-sm font-mono">password</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <form method="GET" class="flex flex-wrap gap-sm items-end bg-surface-container-lowest border border-outline-variant rounded-lg p-md">
            <div>
                <label class="text-label-caps text-on-surface-variant block mb-xs">{{ __('hfnms.role') }}</label>
                <select name="role" class="rounded-lg border-outline-variant text-body-sm">
                    <option value="">{{ __('hfnms.all') }}</option>
                    @foreach ($roles as $role)
                        <option value="{{ $role->name }}" @selected($filters['role'] === $role->name)>{{ __('hfnms.role_'.$role->name) }}</option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="px-md py-sm bg-primary text-on-primary rounded font-semibold text-body-sm hover:opacity-90">{{ __('hfnms.filter') }}</button>
        </form>

        <div class="bg-surface-container-lowest border border-outline-variant rounded-xl overflow-hidden shadow-sm">
            <table class="w-full text-body-sm">
                <thead class="bg-surface-container-low text-on-surface-variant text-label-caps">
                    <tr>
                        <th class="text-left px-lg py-sm">{{ __('hfnms.name') }}</th>
                        <th class="text-left px-lg py-sm">{{ __('hfnms.email') }}</th>
                        <th class="text-left px-lg py-sm">{{ __('hfnms.role') }}</th>
                        <th class="text-left px-lg py-sm">{{ __('hfnms.registered_at') }}</th>
                        <th class="text-right px-lg py-sm"></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($users as $user)
                        <tr class="border-t border-outline-variant hover:bg-surface-container-low/50">
                            <td class="px-lg py-sm font-semibold">
                                {{ $user->name }}
                                @if ($user->id === auth()->id())
                                    <span class="text-[10px] ml-xs px-sm py-xs rounded bg-primary/10 text-primary font-bold uppercase">{{ __('hfnms.you') }}</span>
                                @endif
                            </td>
                            <td class="px-lg py-sm font-mono text-[12px]">{{ $user->email }}</td>
                            <td class="px-lg py-sm">
                                @foreach ($user->roles as $role)
                                    <span class="inline-block px-sm py-xs rounded text-[11px] font-bold uppercase bg-indigo-50 text-indigo-700">{{ __('hfnms.role_'.$role->name) }}</span>
                                @endforeach
                            </td>
                            <td class="px-lg py-sm text-on-surface-variant">{{ $user->created_at?->format('d M Y') }}</td>
                            <td class="px-lg py-sm text-right whitespace-nowrap">
                                <a href="{{ route('users.edit', $user) }}" class="text-primary font-semibold hover:underline mr-md">{{ __('hfnms.edit') }}</a>
                                @if ($user->id !== auth()->id())
                                    <form method="POST" action="{{ route('users.destroy', $user) }}" class="inline"
                                          onsubmit="return confirm('{{ __('hfnms.confirm_delete_user') }}')">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="text-error font-semibold hover:underline">{{ __('hfnms.delete') }}</button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-lg py-xl text-center text-on-surface-variant">{{ __('hfnms.no_users') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($users->hasPages())
            <div>{{ $users->links() }}</div>
        @endif
    </div>
</x-app-layout>
