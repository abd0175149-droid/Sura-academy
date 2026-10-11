<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

/**
 * Wires the modules of the modular monolith (ADR-001, architecture.md §3).
 *
 * Each module may ship `Http/routes/api.php` (mounted under /api/v1 with the `api` group)
 * and `Http/routes/web.php` (`web` group). Policies are found by convention:
 * `<Module>\Models\X` → `<Module>\Policies\XPolicy`.
 */
final class ModulesServiceProvider extends ServiceProvider
{
    /** The eleven code modules. `Jobs` in architecture.md §3 is infrastructure, not a module. */
    public const array MODULES = [
        'Identity', 'Families', 'Curriculum', 'Scheduling', 'Live', 'Ledger',
        'Billing', 'Learning', 'Messaging', 'Teachers', 'Platform',
    ];

    /** Directories every module has, even when empty. */
    public const array DIRECTORIES = [
        'Actions', 'Contracts', 'Events', 'Http', 'Jobs', 'Models', 'Policies', 'Filament',
    ];

    public function boot(): void
    {
        Gate::guessPolicyNamesUsing(self::guessPolicyNames(...));

        foreach (self::MODULES as $module) {
            $this->registerModule(app_path("Modules/{$module}"));
        }
    }

    /** Mounts the route files of the module at the given path. */
    public function registerModule(string $modulePath): void
    {
        if ($this->app->routesAreCached()) {
            return;
        }

        $api = "{$modulePath}/Http/routes/api.php";
        if (is_file($api)) {
            Route::prefix('api/v1')->middleware('api')->group($api);
        }

        $web = "{$modulePath}/Http/routes/web.php";
        if (is_file($web)) {
            Route::middleware('web')->group($web);
        }
    }

    /**
     * @return list<string>
     */
    private static function guessPolicyNames(string $modelClass): array
    {
        $marker = '\\Models\\';
        $pos = strrpos($modelClass, $marker);

        if ($pos === false) {
            return [];
        }

        return [substr($modelClass, 0, $pos).'\\Policies\\'.substr($modelClass, $pos + strlen($marker)).'Policy'];
    }
}
