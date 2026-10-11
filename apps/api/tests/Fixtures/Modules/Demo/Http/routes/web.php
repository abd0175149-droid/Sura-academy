<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

Route::get('demo/page', fn (): string => 'ok')->name('demo.page');
