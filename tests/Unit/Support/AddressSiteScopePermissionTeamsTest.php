<?php

declare(strict_types=1);

use Capell\Address\Models\Address;
use Capell\Address\Support\AddressSiteScope;
use Capell\Core\Models\Site;
use Capell\Tests\Fixtures\Models\User;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function (): void {
    config(['permission.teams' => true]);
    resolve(PermissionRegistrar::class)->teams = true;
    resolve(PermissionRegistrar::class)->forgetCachedPermissions();
});

afterEach(function (): void {
    auth()->logout();
    resolve(PermissionRegistrar::class)->setPermissionsTeamId(null);
    resolve(PermissionRegistrar::class)->teams = false;
    resolve(PermissionRegistrar::class)->forgetCachedPermissions();
    config(['permission.teams' => false]);
});

it('scopes addresses through sites carrying their address identity', function (): void {
    $assignedAddress = Address::factory()->create();
    $otherAddress = Address::factory()->create();
    $assignedSite = Site::factory()->create(['meta' => ['address_id' => $assignedAddress->getKey()]]);
    Site::factory()->create(['meta' => ['address_id' => $otherAddress->getKey()]]);
    $user = User::factory()->create();
    $role = Role::findOrCreate('address-site-scope-test-role', 'web');

    $user->assignRoleForSite($assignedSite, $role);
    resolve(PermissionRegistrar::class)->setPermissionsTeamId($assignedSite->getKey());
    auth()->setUser($user);

    expect(AddressSiteScope::applyForCurrentActor(Address::query())->pluck('id')->all())
        ->toBe([$assignedAddress->getKey()])
        ->and(AddressSiteScope::actorCanUseAddress($user, $assignedAddress))->toBeTrue()
        ->and(AddressSiteScope::actorCanUseAddress($user, $otherAddress))->toBeFalse();
});
