<?php

declare(strict_types=1);

use AlbertoArena\FilamentTruss\Http\Controllers\AssetController;
use Illuminate\Support\Facades\Route;

/**
 * Two static files, served from inside the package.
 *
 * Deliberately ungated, unlike Truss's own asset route. Truss gates its assets
 * so they never confirm the dashboard exists to someone who may not see it;
 * these two carry a handful of layout rules and a dark-mode listener, say
 * nothing about any database, and are useless without the page that uses them.
 * The filenames are an allow-list in the controller, so there is no path to
 * traverse.
 */
Route::get('/filament-truss/assets/{file}', AssetController::class)
    ->name('filament-truss.asset');
