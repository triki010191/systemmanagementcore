<?php

namespace App\Providers;

use App\Services\Cms\ModuleService;
use App\Services\Cms\NavigationService;
use App\Services\Cms\SettingsService;
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
