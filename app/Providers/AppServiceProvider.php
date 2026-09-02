<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

use Illuminate\Support\Facades\Gate;

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
        Gate::before(function ($user, $ability) {
            if ($user->isSuperAdmin()) {
                return true;
            }
        });

        Gate::define('can_configure_pso', fn ($user) => (bool) $user->can_configure_pso);
        Gate::define('can_import_excel', fn ($user) => (bool) $user->can_import_excel);
        Gate::define('can_edit_bills', fn ($user) => (bool) $user->can_edit_bills);
        Gate::define('can_record_corrections', fn ($user) => (bool) $user->can_record_corrections);
        Gate::define('can_record_credit', fn ($user) => (bool) $user->can_record_credit);
        Gate::define('can_approve_sealing', fn ($user) => (bool) $user->can_approve_sealing);
        Gate::define('can_edit_cutoff', fn ($user) => (bool) $user->can_edit_cutoff);
        Gate::define('can_manage_users', fn ($user) => (bool) $user->can_manage_users);
    }
}
