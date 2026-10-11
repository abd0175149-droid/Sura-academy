<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

it('Platform: users never serialize credentials and passwords are stored hashed', function (): void {
    $user = User::factory()->create(['password' => 'plain-secret']);

    expect($user->toArray())->not->toHaveKeys(['password', 'remember_token'])
        ->and($user->getAttributes()['password'])->not->toBe('plain-secret')
        ->and(Hash::check('plain-secret', $user->password))->toBeTrue();
});

it('Platform: the Horizon dashboard is closed outside the local environment', function (): void {
    $user = User::factory()->create();

    expect(Gate::forUser($user)->allows('viewHorizon'))->toBeFalse();
    $this->actingAs($user)->get('/horizon')->assertForbidden();
    $this->get('/horizon')->assertForbidden();
});
