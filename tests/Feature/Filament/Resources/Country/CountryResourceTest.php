<?php

declare(strict_types=1);

use Capell\Address\Filament\Resources\Countries\CountryResource;
use Capell\Address\Filament\Resources\Countries\Pages\ManageCountries;
use Capell\Tests\Support\Concerns\CreatesAdminUser;

use function Pest\Laravel\get;
use function Pest\Livewire\livewire;

uses(CreatesAdminUser::class)
    ->group('country');

test('admin can see countries', function (): void {
    test()->actingAsAdmin();

    livewire(ManageCountries::class)
        ->assertSuccessful();
});

test('cannot see countries', function (): void {
    test()->actingAsUser();

    get(CountryResource::getUrl())
        ->assertForbidden();
});
