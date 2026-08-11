<?php

namespace App\Providers;

use App\Services\MasterDataModuleService;
use App\Services\TenantResolver;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(TenantResolver::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        View::composer('layouts.app', function ($view) {
            $user = auth()->user();

            if ($user && $user->isAdmin() && ! $user->isSuperAdmin()) {
                $view->with('moduleCounts', app(MasterDataModuleService::class)->moduleCounts());
            } else {
                $view->with('moduleCounts', []);
            }
        });
    }
}
