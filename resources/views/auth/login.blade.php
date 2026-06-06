<x-auth-layout>
    <section class="bg-surface-container-lowest border border-outline-variant rounded-xl p-xl shadow-sm">
        <x-auth-session-status class="mb-4" :status="session('status')" />

        <form method="POST" action="{{ route('login') }}" class="space-y-lg" x-data="{ showPassword: false, submitting: false }" @submit="submitting = true">
            @csrf

            <div class="space-y-xs">
                <label class="text-label-caps text-on-surface-variant uppercase" for="email">{{ __('hfnms.username_email') }}</label>
                <div class="relative left-border-trace transition-all">
                    <div class="absolute inset-y-0 left-0 pl-md flex items-center pointer-events-none">
                        <span class="material-symbols-outlined text-outline text-[18px]">person</span>
                    </div>
                    <input
                        id="email"
                        type="email"
                        name="email"
                        value="{{ old('email') }}"
                        required
                        autofocus
                        autocomplete="username"
                        placeholder="{{ __('hfnms.email_placeholder') }}"
                        class="w-full bg-surface-container-low border border-outline-variant rounded-lg py-[10px] pl-[44px] pr-md text-body-md focus:outline-none focus:ring-2 focus:ring-primary-container focus:border-transparent transition-all placeholder:text-outline @error('email') border-red-500 @enderror"
                    >
                </div>
                <x-input-error :messages="$errors->get('email')" class="text-body-sm text-red-600" />
            </div>

            <div class="space-y-xs">
                <div class="flex justify-between items-center">
                    <label class="text-label-caps text-on-surface-variant uppercase" for="password">{{ __('hfnms.password') }}</label>
                    @if (Route::has('password.request'))
                        <a href="{{ route('password.request') }}" class="text-body-sm text-primary hover:underline transition-all">
                            {{ __('hfnms.forgot_password') }}
                        </a>
                    @endif
                </div>
                <div class="relative left-border-trace transition-all">
                    <div class="absolute inset-y-0 left-0 pl-md flex items-center pointer-events-none">
                        <span class="material-symbols-outlined text-outline text-[18px]">lock</span>
                    </div>
                    <input
                        id="password"
                        name="password"
                        required
                        autocomplete="current-password"
                        placeholder="••••••••"
                        class="w-full bg-surface-container-low border border-outline-variant rounded-lg py-[10px] pl-[44px] pr-[44px] text-body-md focus:outline-none focus:ring-2 focus:ring-primary-container focus:border-transparent transition-all placeholder:text-outline @error('password') border-red-500 @enderror"
                        :type="showPassword ? 'text' : 'password'"
                    >
                    <button
                        type="button"
                        class="absolute inset-y-0 right-0 pr-md flex items-center text-outline hover:text-on-surface transition-all"
                        @click="showPassword = !showPassword"
                        tabindex="-1"
                    >
                        <span class="material-symbols-outlined text-[18px]" x-text="showPassword ? 'visibility_off' : 'visibility'"></span>
                    </button>
                </div>
                <x-input-error :messages="$errors->get('password')" class="text-body-sm text-red-600" />
            </div>

            <div class="flex items-center">
                <input
                    id="remember_me"
                    type="checkbox"
                    name="remember"
                    class="w-4 h-4 text-primary border-outline-variant rounded focus:ring-primary focus:ring-offset-0 transition-all cursor-pointer"
                >
                <label for="remember_me" class="ml-sm text-body-sm text-on-surface-variant select-none cursor-pointer">
                    {{ __('hfnms.remember_me') }}
                </label>
            </div>

            <button
                type="submit"
                class="w-full bg-primary text-on-primary font-bold py-3 rounded-lg shadow-sm hover:bg-on-primary-fixed-variant active:scale-[0.98] transition-all flex items-center justify-center gap-sm disabled:opacity-70"
                :disabled="submitting"
            >
                <span x-show="!submitting">{{ __('hfnms.login_button') }}</span>
                <span x-show="submitting" x-cloak>{{ __('hfnms.login_verifying') }}</span>
                <span class="material-symbols-outlined text-[18px]" x-show="!submitting">login</span>
                <svg x-show="submitting" x-cloak class="animate-spin h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
            </button>
        </form>

        <div class="mt-lg pt-lg border-t border-outline-variant">
            <div class="flex items-center gap-sm text-on-surface-variant">
                <span class="material-symbols-outlined text-[16px]">verified_user</span>
                <p class="text-body-sm">{{ __('hfnms.security_notice') }}</p>
            </div>
        </div>
    </section>
</x-auth-layout>
