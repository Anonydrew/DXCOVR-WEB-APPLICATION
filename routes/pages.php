<?php

use App\Http\Controllers\Site\PageController;
use Illuminate\Support\Facades\Route;

/*
 * Custom and legal pages managed in Admin → Pages. Registered after every
 * other route so fixed routes always win; reserved slugs are blocked by
 * validation when pages are saved.
 */
Route::get('/{page:slug}', [PageController::class, 'show'])
    ->where('page', '[a-z0-9]+(?:-[a-z0-9]+)*')
    ->name('pages.show');
