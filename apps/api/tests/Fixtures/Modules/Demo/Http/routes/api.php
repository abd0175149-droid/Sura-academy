<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

Route::get('demo/ping', fn (): array => ['pong' => true])->name('demo.ping');
