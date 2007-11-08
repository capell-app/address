<?php

declare(strict_types=1);

use Capell\Address\Actions\SeedAddressScreenshotFixtureAction;
use Capell\Core\Models\Site;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth'])->get('/screenshot-fixtures/address/site', static function (): RedirectResponse {
    SeedAddressScreenshotFixtureAction::run();
    $site = Site::query()->firstOrFail();
    $routeKey = $site->getRouteKey();
    throw_unless(is_int($routeKey) || is_string($routeKey), RuntimeException::class, 'The address screenshot site route key must be scalar.');

    return redirect('/admin/sites/' . $routeKey . '/edit');
});
