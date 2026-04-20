<?php

namespace App\Providers;

use App\Listeners\DecorateApplicationsMenu;
use App\Models\User;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use JeroenNoten\LaravelAdminLte\Events\BuildingMenu;

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
        // Menu-visibility gates for AdminLTE sidebar (config/adminlte.php uses 'can' keys).
        Gate::define('access-admin', fn (User $u) => $u->isAdminOrApplication());
        Gate::define('access-my',    fn (User $u) => $u->isCsAgent() || $u->isAdmin());

        Event::listen(BuildingMenu::class, DecorateApplicationsMenu::class);
    }
}
