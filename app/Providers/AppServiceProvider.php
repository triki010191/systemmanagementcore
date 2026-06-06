<?php

namespace App\Providers;

use App\Services\Cms\ModuleService;
use App\Services\Cms\NavigationService;
use App\Services\Cms\SettingsService;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $appUrl = (string) config('app.url');
        $host = parse_url($appUrl, PHP_URL_HOST) ?: '';

        if ($host !== '' && ! in_array($host, ['127.0.0.1', 'localhost'], true)) {
            URL::forceRootUrl(rtrim($appUrl, '/'));

            if (str_starts_with($appUrl, 'https://')) {
                URL::forceScheme('https');
            }
        }

        View::composer(['layouts.app', 'dashboard', 'network.*', 'components.network.*', 'admin.*'], function ($view) {
            $view->with('cmsSettings', app(SettingsService::class)->getPublic());
            $view->with('cmsNavigation', app(NavigationService::class)->forLocation('sidebar'));
            $view->with('cmsModules', app(ModuleService::class)->all());
        });

        View::composer(['layouts.guest', 'components.auth-layout', 'auth.*'], function ($view) {
            $view->with('cmsSettings', app(SettingsService::class)->getPublic());
        });
    }
}
