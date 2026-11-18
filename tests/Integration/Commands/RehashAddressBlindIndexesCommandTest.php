<?php

declare(strict_types=1);

use Capell\Address\Models\Address;
use Capell\Address\Models\Country;
use Capell\Address\Support\AddressBlindIndex;
use Illuminate\Support\Facades\DB;

function storedAddressLine1Hash(Address $address): ?string
{
    $hash = DB::table($address->getTable())->where('id', $address->getKey())->value('line1_hash');

    return is_string($hash) ? $hash : null;
}

it('builds the blind index from the dedicated key instead of the application key', function (): void {
    config()->set('capell-address.blind_index_key', null);
    $withAppKey = Address::factory()->create(['line1' => '1 Key Street', 'postal_code' => 'AB1 2CD']);

    config()->set('capell-address.blind_index_key', 'dedicated-blind-index-secret');
    $withDedicatedKey = Address::factory()->create(['line1' => '1 Key Street', 'postal_code' => 'AB1 2CD']);

    expect(storedAddressLine1Hash($withAppKey))->not->toBeNull()
        ->and(storedAddressLine1Hash($withDedicatedKey))->not->toBeNull()
        ->and(storedAddressLine1Hash($withDedicatedKey))->not->toBe(storedAddressLine1Hash($withAppKey))
        ->and(storedAddressLine1Hash($withDedicatedKey))->toBe(hash_hmac('sha256', '1 key street', 'dedicated-blind-index-secret'));
});

it('keeps address lookup working after the blind-index key is rotated and indexes are rehashed', function (): void {
    config()->set('capell-address.blind_index_key', 'old-secret');
    $country = Country::factory()->create();
    $address = Address::factory()->create([
        'line1' => '22 Rotation Road',
        'postal_code' => 'RO7 4TE',
        'country_id' => $country->getKey(),
    ]);
    $trashed = Address::factory()->create(['line1' => '23 Rotation Road', 'postal_code' => 'RO7 4TF']);
    $trashed->delete();

    config()->set('capell-address.blind_index_key', 'new-secret');

    expect(Address::findAddress('22 Rotation Road', 'RO7 4TE', (int) $country->getKey()))->toBeNull();

    $this->artisan('capell:address-blind-index-rehash', ['--chunk' => 1])
        ->expectsOutputToContain(__('capell-address::blind_index.rehashed', ['changed' => 2]))
        ->assertSuccessful();

    expect(Address::findAddress(' 22 rotation road ', 'ro7 4te', (int) $country->getKey())?->getKey())->toBe($address->getKey())
        ->and(storedAddressLine1Hash($trashed))->toBe(hash_hmac('sha256', '23 rotation road', 'new-secret'));

    $this->artisan('capell:address-blind-index-rehash')
        ->expectsOutputToContain(__('capell-address::blind_index.rehashed', ['changed' => 0]))
        ->assertSuccessful();
});

it('falls back to the application key when no dedicated key is configured', function (): void {
    config()->set('capell-address.blind_index_key', '');
    $appKey = config()->string('app.key');

    $address = Address::factory()->create(['line1' => '5 Fallback Lane']);

    expect($appKey)->not->toBe('')
        ->and(storedAddressLine1Hash($address))->toBe(hash_hmac('sha256', '5 fallback lane', $appKey));

    config()->set('app.key', '');

    expect(fn (): ?string => AddressBlindIndex::hash('6 Fallback Lane'))
        ->toThrow(RuntimeException::class, 'Address blind-index secret is unavailable.');
});
