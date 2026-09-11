<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Blade;

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
        // Blade date formatting directive: dd/mm/yyyy
        Blade::directive('date', function ($expression) {
            return "<?php echo ($expression) ? (is_string($expression) ? date('d/m/Y', strtotime($expression)) : ($expression)->format('d/m/Y')) : ''; ?>";
        });

        Blade::directive('datetime', function ($expression) {
            return "<?php echo ($expression) ? (is_string($expression) ? date('d/m/Y H:i', strtotime($expression)) : ($expression)->format('d/m/Y H:i')) : ''; ?>";
        });

        Gate::before(function ($user, $ability) {
            if ($user->isSuperAdmin()) {
                return true;
            }
        });

        Gate::define('can_configure_pso', fn ($user) => (bool) $user->hasPermission('can_configure_pso'));
        Gate::define('can_manage_prefixes', fn ($user) => (bool) $user->hasPermission('can_manage_prefixes'));
        Gate::define('can_manage_salespersons', fn ($user) => (bool) $user->hasPermission('can_manage_salespersons'));
        Gate::define('can_create_pso', fn ($user) => (bool) $user->hasPermission('can_create_pso'));
        Gate::define('can_edit_pso', fn ($user) => (bool) $user->hasPermission('can_edit_pso'));
        Gate::define('can_delete_pso', fn ($user) => (bool) $user->hasPermission('can_delete_pso'));
        Gate::define('can_close_pso', fn ($user) => (bool) $user->hasPermission('can_close_pso'));
        Gate::define('can_import_excel', fn ($user) => (bool) $user->hasPermission('can_import_excel'));
        Gate::define('can_edit_bills', fn ($user) => (bool) $user->hasPermission('can_edit_bills'));
        Gate::define('can_record_corrections', fn ($user) => (bool) $user->hasPermission('can_record_corrections'));
        Gate::define('can_record_credit', fn ($user) => (bool) $user->hasPermission('can_record_credit'));
        Gate::define('can_approve_sealing', fn ($user) => (bool) $user->hasPermission('can_approve_sealing'));
        Gate::define('can_edit_cutoff', fn ($user) => (bool) $user->hasPermission('can_edit_cutoff'));
        Gate::define('can_manage_users', fn ($user) => (bool) $user->hasPermission('can_manage_users'));
    }
}
