<?php

declare(strict_types=1);

namespace Capell\Address\Providers;

use Capell\Core\Support\Migration\MigrationRootRegistry;
use Illuminate\Support\ServiceProvider;
use Override;

final class MigrationMetadataServiceProvider extends ServiceProvider
{
    #[Override]
    public function register(): void
    {
        $this->callAfterResolving(MigrationRootRegistry::class, static function (MigrationRootRegistry $registry): void {
            $registry->register('capell-app/address', __DIR__ . '/../../database/migrations', [
                '2026_05_10_190839_01_create_countries_table' => 'countries',
                '2026_05_10_190839_02_create_addresses_table' => 'addresses',
            ]);
        });
    }
}
