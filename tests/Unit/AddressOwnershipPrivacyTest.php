<?php

declare(strict_types=1);

use Capell\Address\Actions\BuildAddressPrivacyExportAction;
use Capell\Address\Actions\CloneSharedAddressForMutationAction;
use Capell\Address\Actions\EnsureSiteOwnsAddressAction;
use Capell\Address\Actions\EraseAddressPrivacyDataAction;
use Capell\Address\Models\Address;
use Capell\Core\Models\Site;
use Capell\Tests\Support\MigrationRunner;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

it('drops every plaintext composite index before encrypting address columns', function (): void {
    Schema::drop('addresses');
    MigrationRunner::up(__DIR__ . '/../../database/migrations/2026_05_10_190839_02_create_addresses_table.php');

    $indexes = [
        'address_part_index' => ['line1', 'postal_code'],
        'address_full_index' => ['line1', 'line2', 'city', 'state', 'postal_code', 'country_id'],
        'addresses_city_state_country_id_index' => ['city', 'state', 'country_id'],
        'addresses_state_postal_code_country_id_index' => ['state', 'postal_code', 'country_id'],
    ];
    Schema::table('addresses', function (Blueprint $table) use ($indexes): void {
        foreach ($indexes as $name => $columns) {
            if (! Schema::hasIndex('addresses', $name)) {
                $table->index($columns, $name);
            }
        }
    });
    DB::table('addresses')->insert(['line1' => 'Private street', 'city' => 'London']);

    MigrationRunner::up(__DIR__ . '/../../database/migrations/2026_07_12_000001_add_address_ownership_and_encrypt_meta.php');

    foreach (array_keys($indexes) as $index) {
        expect(Schema::hasIndex('addresses', $index))->toBeFalse();
    }
    $address = DB::table('addresses')->first();
    throw_unless($address !== null, RuntimeException::class);
    expect(Crypt::decryptString((string) $address->line1))->toBe('Private street')
        ->and(Schema::hasIndex('addresses', 'address_blind_lookup_index'))->toBeTrue();
});

it('clones a cross-owner address before mutation and keeps the original unchanged', function (): void {
    $address = Address::factory()->create(['site_id' => 1, 'line1' => 'Original', 'meta' => ['latitude' => 51.5, 'longitude' => -0.1]]);

    $clone = CloneSharedAddressForMutationAction::run($address, ['line1' => 'Tenant-specific'], siteId: 2);

    expect($clone->is($address))->toBeFalse()
        ->and($clone->line1)->toBe('Tenant-specific')
        ->and($address->refresh()->line1)->toBe('Original')
        ->and((string) $address->getRawOriginal('meta'))->not->toContain('latitude')
        ->and((string) $address->getRawOriginal('line1'))->not->toContain('Original')
        ->and($address->getRawOriginal('line1_hash'))->toBeString()
        ->and($address->getRawOriginal('postal_code_hash'))->toBeString();
});

it('exports and erases addresses owned by a privacy subject site', function (): void {
    $site = Site::factory()->create();
    Address::factory()->create(['site_id' => $site->getKey(), 'meta' => ['latitude' => 51.5]]);
    Address::factory()->create(['site_id' => Site::factory()->create()->getKey()]);

    expect(BuildAddressPrivacyExportAction::run($site))->toHaveKey('addresses.0.meta.latitude', 51.5)
        ->and(EraseAddressPrivacyDataAction::run($site))->toBe(1)
        ->and(Address::query()->count())->toBe(1);
});

it('claims an unowned address and clones it when another site attaches it', function (): void {
    $address = Address::factory()->create();
    $firstSite = Site::factory()->create(['meta' => ['address_id' => $address->getKey()]]);
    $secondSite = Site::factory()->create(['meta' => ['address_id' => $address->getKey()]]);

    $firstAddress = EnsureSiteOwnsAddressAction::run($firstSite);
    $secondAddress = EnsureSiteOwnsAddressAction::run($secondSite);

    expect($firstAddress?->site_id)->toBe($firstSite->getKey())
        ->and($secondAddress?->site_id)->toBe($secondSite->getKey())
        ->and($secondAddress?->getKey())->not->toBe($firstAddress?->getKey())
        ->and(data_get($secondSite->refresh()->meta, 'address_id'))->toBe($secondAddress?->getKey());
});
