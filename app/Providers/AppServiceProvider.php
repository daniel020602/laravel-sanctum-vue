<?php

namespace App\Providers;

use App\Models\Menu;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Gate;
use App\Models\Sub;
use App\Policies\MenuPolicy;
use App\Policies\SubPolicy;

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
        Gate::define('admin', [MenuPolicy::class, 'admin']);
        Gate::policy(SubPolicy::class,Sub::class );
        Gate::policy(Menu::class, MenuPolicy::class);
    }
    
}

