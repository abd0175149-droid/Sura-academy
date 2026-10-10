<?php

declare(strict_types=1);

use App\Providers\ModulesServiceProvider;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Tests\Fixtures\Modules\Demo\Models\Widget;
use Tests\Fixtures\Modules\Demo\Policies\WidgetPolicy;

beforeEach(function (): void {
    $provider = app()->getProvider(ModulesServiceProvider::class);
    expect($provider)->toBeInstanceOf(ModulesServiceProvider::class);

    $provider->registerModule(base_path('tests/Fixtures/Modules/Demo'));
    Route::getRoutes()->refreshNameLookups();
});

it('Platform: module api routes are registered under /api/v1 with the api middleware', function (): void {
    $this->getJson('/api/v1/demo/ping')->assertOk()->assertExactJson(['pong' => true]);

    expect(Route::getRoutes()->getByName('demo.ping')?->gatherMiddleware())->toContain('api');
});

it('Platform: module web routes are registered with the web middleware', function (): void {
    $this->get('/demo/page')->assertOk();

    expect(Route::getRoutes()->getByName('demo.page')?->gatherMiddleware())->toContain('web');
});

it('Platform: module policies are discovered by convention', function (): void {
    expect(Gate::getPolicyFor(Widget::class))->toBeInstanceOf(WidgetPolicy::class);
});

it('Platform: every listed module is registered at boot', function (): void {
    expect(ModulesServiceProvider::MODULES)->toBe([
        'Identity', 'Families', 'Curriculum', 'Scheduling', 'Live', 'Ledger',
        'Billing', 'Learning', 'Messaging', 'Teachers', 'Platform',
    ]);
});
