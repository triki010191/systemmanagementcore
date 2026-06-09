<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-md w-full">
            <div>
                <h2 class="font-semibold text-headline-md text-on-surface">{{ __('hfnms.join_closure') }}</h2>
                <p class="text-body-sm text-on-surface-variant">{{ __('hfnms.join_closure_subtitle') }}</p>
            </div>
            @can('cable.manage')
                <a href="{{ route('core-joints.create') }}"
                   class="inline-flex items-center gap-xs px-md py-sm bg-primary text-on-primary rounded font-semibold text-body-sm hover:opacity-90">
                    <span class="material-symbols-outlined text-[18px]">add</span>
                    {{ __('hfnms.add_joint') }}
                </a>
            @endcan
        </div>
    </x-slot>

    <div class="max-w-[1600px] mx-auto">
        @if (session('success'))
            <div class="mb-md px-md py-sm bg-green-50 border border-green-200 text-success rounded-lg text-body-sm">{{ session('success') }}</div>
        @endif

        <div class="bg-surface-container-lowest border border-outline-variant rounded-lg overflow-x-auto">
            <table class="w-full text-body-sm">
                <thead class="bg-surface-container-low text-on-surface-variant text-label-caps">
                    <tr>
                        <th class="text-left px-lg py-sm">{{ __('hfnms.joint_code') }}</th>
                        <th class="text-left px-lg py-sm">{{ __('hfnms.joint_type') }}</th>
                        <th class="text-left px-lg py-sm">{{ __('hfnms.joint_route') }}</th>
                        <th class="text-left px-lg py-sm">{{ __('hfnms.shared_core_number') }}</th>
                        <th class="text-left px-lg py-sm">{{ __('hfnms.splice_loss') }}</th>
                        <th class="text-left px-lg py-sm">{{ __('hfnms.status') }}</th>
                        <th class="text-right px-lg py-sm"></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($joints as $joint)
                        <tr class="border-t border-outline-variant hover:bg-surface-container-low/50">
                            <td class="px-lg py-sm font-mono text-primary font-semibold">{{ $joint->code }}</td>
                            <td class="px-lg py-sm">{{ $joint->joint_type?->label() ?? '—' }}</td>
                            <td class="px-lg py-sm font-mono text-[12px]">
                                {{ $joint->sourceNode?->code ?? '—' }} → {{ $joint->targetNode?->code ?? '—' }}
                            </td>
                            <td class="px-lg py-sm font-mono font-semibold text-primary">
                                Core {{ $joint->core_number ?? '—' }}
                            </td>
                            <td class="px-lg py-sm">{{ $joint->splice_loss_db ?? '—' }} dB</td>
                            <td class="px-lg py-sm">{{ ucfirst($joint->status) }}</td>
                            <td class="px-lg py-sm text-right space-x-sm">
                                @can('cable.manage')
                                    <a href="{{ route('core-joints.edit', $joint) }}" class="text-primary font-semibold hover:underline">{{ __('hfnms.edit') }}</a>
                                    <form method="POST" action="{{ route('core-joints.destroy', $joint) }}" class="inline" onsubmit="return confirm('{{ __('hfnms.confirm_delete') }}')">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="text-error font-semibold hover:underline">{{ __('hfnms.delete') }}</button>
                                    </form>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-lg py-lg text-center text-on-surface-variant">{{ __('hfnms.no_joints') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-md">{{ $joints->links() }}</div>
    </div>
</x-app-layout>
