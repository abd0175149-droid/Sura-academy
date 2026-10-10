<?php

declare(strict_types=1);

use App\Providers\ModulesServiceProvider;

// ADR-001: a module talks to another only through its Contracts\* or events, never its Models.
foreach (ModulesServiceProvider::MODULES as $module) {
    $foreignModels = array_values(array_map(
        fn (string $other): string => "App\\Modules\\{$other}\\Models",
        array_filter(ModulesServiceProvider::MODULES, fn (string $other): bool => $other !== $module),
    ));

    arch("Arch: {$module} does not use another module's Models")
        ->expect("App\\Modules\\{$module}")
        ->not->toUse($foreignModels);
}

it('Arch: every module has the standard directories', function (): void {
    foreach (ModulesServiceProvider::MODULES as $module) {
        foreach (ModulesServiceProvider::DIRECTORIES as $dir) {
            expect(app_path("Modules/{$module}/{$dir}"))->toBeDirectory();
        }
    }
});

arch('Arch: php preset')->preset()->php();

arch('Arch: security preset')->preset()->security();
