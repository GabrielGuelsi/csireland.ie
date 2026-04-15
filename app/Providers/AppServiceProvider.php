<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Support\Facades\Gate;
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
        // Menu-visibility gates for AdminLTE sidebar (config/adminlte.php uses 'can' keys).
        Gate::define('access-admin', fn (User $u) => $u->isAdminOrApplication());
        Gate::define('access-my',    fn (User $u) => $u->isCsAgent() || $u->isAdmin());
    }
}
