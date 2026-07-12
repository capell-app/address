<?php

declare(strict_types=1);

use Capell\Address\Filament\Resources\Addresses\AddressResource;
use Capell\Address\Filament\Resources\Addresses\Pages\ManageAddresses;
use Capell\Tests\Support\Concerns\CreatesAdminUser;

use function Pest\Laravel\get;
use function Pest\Livewire\livewire;

uses(CreatesAdminUser::class)
    ->group('address');

test('admin can see addresses', function (): void {
    test()->actingAsAdmin();

    livewire(ManageAddresses::class)
        ->assertSuccessful();
});

test('cannot see addresses', function (): void {
    test()->actingAsUser();

    get(AddressResource::getUrl())
        ->assertForbidden();
});
