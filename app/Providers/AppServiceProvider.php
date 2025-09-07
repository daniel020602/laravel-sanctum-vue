<?php

namespace App\Providers;

use App\Models\Menu;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Gate;
use App\Policies\MenuPolicy;
use App\Policies\OrderPolicy;
use App\Models\Order;
use App\Policies\TablePolicy;
use App\Models\Table;
use App\Models\Week;
use App\Policies\WeekPolicy;
use App\Models\Subscription;
use App\Policies\SubscriptionPolicy;


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
        Gate::policy(Menu::class, MenuPolicy::class);
        Gate::define('ownerOrAdmin', [OrderPolicy::class, 'ownerOrAdmin']);
        Gate::policy(Order::class, OrderPolicy::class);
        Gate::policy(Table::class, TablePolicy::class);
        Gate::policy(Week::class, WeekPolicy::class);
        Gate::policy(Subscription::class, SubscriptionPolicy::class);
    }
    
}

