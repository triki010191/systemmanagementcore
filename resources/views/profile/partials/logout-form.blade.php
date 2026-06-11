<section class="space-y-md">
    <header>
        <h2 class="text-headline-sm font-semibold text-on-surface">
            {{ __('hfnms.logout') }}
        </h2>

        <p class="mt-xs text-body-sm text-on-surface-variant">
            {{ __('hfnms.logout_description') }}
        </p>
    </header>

    <form method="POST" action="{{ route('logout') }}">
        @csrf

        <button
            type="submit"
            class="inline-flex items-center gap-sm px-md py-sm border border-outline-variant bg-surface-container-low text-on-surface rounded-lg font-semibold text-body-sm hover:bg-surface-container transition-all"
        >
            <span class="material-symbols-outlined text-[18px]">logout</span>
            {{ __('hfnms.logout') }}
        </button>
    </form>
</section>
