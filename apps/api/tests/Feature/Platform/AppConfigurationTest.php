<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

it('Platform: app runs in UTC with Arabic locale and English fallback', function (): void {
    expect(config('app.timezone'))->toBe('UTC')
        ->and(date_default_timezone_get())->toBe('UTC')
        ->and(now()->getTimezone()->getName())->toBe('UTC')
        ->and(app()->getLocale())->toBe('ar')
        ->and(app()->getFallbackLocale())->toBe('en');
});

it('Platform: tests run on PostgreSQL in UTC', function (): void {
    expect(DB::connection()->getDriverName())->toBe('pgsql')
        ->and(DB::scalar("select current_setting('TimeZone')"))->toBe('UTC');
});

it('Platform: default migrations do not create a sessions table', function (): void {
    expect(Schema::hasTable('users'))->toBeTrue()
        ->and(Schema::hasTable('sessions'))->toBeFalse();
});
