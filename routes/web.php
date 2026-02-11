<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| SPA Entry Point
|--------------------------------------------------------------------------
|
| All non-API routes serve the Vue SPA. Vue Router handles client-side routing.
| The pattern excludes api/* and admin/api/* to prevent catching API calls.
|
*/

Route::get('/{any?}', fn () => view('app'))
    ->where('any', '^(?!api/|admin/api/|up).*$')
    ->name('spa');
