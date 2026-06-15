<?php

declare(strict_types=1);

use Capell\Address\Console\Commands\DemoCommand;
use Capell\Address\Console\Commands\FakerCommand;
use Capell\Address\Console\Commands\InstallCommand;
use Capell\Address\Filament\Configurators\Addresses\DefaultAddressConfigurator;
use Capell\Address\Filament\Configurators\Countries\DefaultCountryConfigurator;
use Capell\Address\Filament\Configurators\Languages\DefaultLanguageConfigurator;
use Capell\Address\Filament\Resources\Addresses\AddressResource;
use Capell\Address\Filament\Resources\Countries\CountryResource;
use Capell\Address\Filament\Resources\Sites\Schemas\Extenders\SiteSchemaExtender;
use Capell\Address\Health\AddressHealthCheck;
use Capell\Address\Manifest\AddressAdminAssetsContribution;
use Capell\Address\Manifest\AddressConfiguratorsContribution;
use Capell\Address\Manifest\AddressConsoleCommandsContribution;
use Capell\Address\Manifest\AddressMigrationsContribution;
use Capell\Address\Manifest\AddressModelsContribution;
use Capell\Address\Manifest\AddressResourceContribution;
use Capell\Address\Manifest\AddressSiteSchemaExtenderContribution;
use Capell\Address\Manifest\CountryResourceContribution;
use Capell\Address\Models\Address;
use Capell\Address\Models\Country;
use Capell\Core\Contracts\Extensions\ChecksExtensionHealth;
use Capell\Core\Contracts\Extensions\ExtensionContribution;
use Capell\Core\Contracts\Extensions\RegistersExtensionAdminResource;
use Capell\Core\Contracts\Extensions\RegistersExtensionAsset;
use Capell\Core\Contracts\Extensions\RunsExtensionMigration;
use Capell\Core\Support\Manifest\ManifestValidator;
use Illuminate\Support\Facades\File;

describe('address capell.json manifest', function (): void {
    it('declares requires using full composer package names', function (): void {
        $manifest = json_decode(
            File::get(__DIR__ . '/../../capell.json'),
            associative: true,
        );

        $requires = $manifest['dependencies']['requires'] ?? [];

        foreach ($requires as $requirement) {
            expect($requirement)->toContain('/');
        }
    });

    it('requires capell-app/admin as a dependency', function (): void {
        $manifest = json_decode(
            File::get(__DIR__ . '/../../capell.json'),
            associative: true,
        );

        expect($manifest['dependencies']['requires'])->toContain('capell-app/admin');
    });

    it('declares its demo command for package demo installs', function (): void {
        $manifest = json_decode(
            File::get(__DIR__ . '/../../capell.json'),
            associative: true,
        );

        expect($manifest['commands']['demo'])->toBe('capell:address-demo');
    });

    it('declares shipped extension contributions', function (): void {
        $packagePath = dirname(__DIR__, 2);
        $manifest = capell_json_file_array($packagePath . '/capell.json');
        $composer = capell_json_file_array($packagePath . '/composer.json');
        $contributions = data_get($manifest, 'contributes');

        throw_unless(is_array($contributions), RuntimeException::class, 'Expected Address manifest contributions.');

        (new ManifestValidator)->validate($manifest, $composer, 'capell-app/address', $packagePath . '/capell.json');

        expect($manifest)
            ->toHaveKey('manifest-version', 3)
            ->toHaveKey('name', 'capell-app/address')
            ->toHaveKey('namespace', 'Capell\\Address')
            ->and(data_get($manifest, 'surfaces', []))->toContain('admin')
            ->and(data_get($manifest, 'database.requiredTables', []))->toBe(['countries', 'addresses'])
            ->and(data_get($manifest, 'commands.install'))->toBe('capell:address-install')
            ->and(data_get($manifest, 'commands.faker'))->toBe('capell:address-faker')
            ->and(data_get($manifest, 'commands.demo'))->toBe('capell:address-demo')
            ->and(data_get($manifest, 'healthChecks.0.class'))->toBe(AddressHealthCheck::class)
            ->and(data_get($manifest, 'contributionTraceability.deferredContributions'))->toBe([]);

        expect($contributions)
            ->toContain([
                'type' => 'admin-resource',
                'class' => AddressResourceContribution::class,
                'resourceClass' => AddressResource::class,
            ])
            ->toContain([
                'type' => 'admin-resource',
                'class' => CountryResourceContribution::class,
                'resourceClass' => CountryResource::class,
            ])
            ->toContain([
                'type' => 'configurator',
                'class' => AddressConfiguratorsContribution::class,
                'configuratorClasses' => [
                    DefaultAddressConfigurator::class,
                    DefaultCountryConfigurator::class,
                    DefaultLanguageConfigurator::class,
                ],
                'groups' => ['Addresses', 'Countries', 'Language'],
            ])
            ->toContain([
                'type' => 'schema-extender',
                'class' => AddressSiteSchemaExtenderContribution::class,
                'extenderClass' => SiteSchemaExtender::class,
                'tag' => 'Site',
            ])
            ->toContain([
                'type' => 'model',
                'class' => AddressModelsContribution::class,
                'modelClasses' => [
                    Address::class,
                    Country::class,
                ],
            ])
            ->toContain([
                'type' => 'asset',
                'class' => AddressAdminAssetsContribution::class,
                'surface' => 'admin',
                'assets' => ['capell-address', 'blade-country-flags'],
            ])
            ->toContain([
                'type' => 'migration',
                'class' => AddressMigrationsContribution::class,
                'tables' => ['countries', 'addresses'],
            ])
            ->toContain([
                'type' => 'console-command',
                'class' => AddressConsoleCommandsContribution::class,
                'commands' => [
                    'capell:address-install',
                    'capell:address-faker',
                    'capell:address-demo',
                ],
                'commandClasses' => [
                    InstallCommand::class,
                    FakerCommand::class,
                    DemoCommand::class,
                ],
            ])
            ->toContain([
                'type' => 'health-check',
                'class' => AddressHealthCheck::class,
            ]);

        expect(class_implements(AddressResourceContribution::class))->toContain(RegistersExtensionAdminResource::class)
            ->and(class_implements(CountryResourceContribution::class))->toContain(RegistersExtensionAdminResource::class)
            ->and(class_implements(AddressAdminAssetsContribution::class))->toContain(RegistersExtensionAsset::class)
            ->and(class_implements(AddressMigrationsContribution::class))->toContain(RunsExtensionMigration::class)
            ->and(class_implements(AddressConfiguratorsContribution::class))->toContain(ExtensionContribution::class)
            ->and(class_implements(AddressConsoleCommandsContribution::class))->toContain(ExtensionContribution::class)
            ->and(class_implements(AddressModelsContribution::class))->toContain(ExtensionContribution::class)
            ->and(class_implements(AddressSiteSchemaExtenderContribution::class))->toContain(ExtensionContribution::class)
            ->and(class_implements(AddressHealthCheck::class))->toContain(ChecksExtensionHealth::class);
    });

    it('keeps composer package requirements aligned with the manifest', function (): void {
        $manifest = json_decode(
            File::get(__DIR__ . '/../../capell.json'),
            associative: true,
        );
        $composer = json_decode(
            File::get(__DIR__ . '/../../composer.json'),
            associative: true,
        );

        $composerPackageRequirements = array_values(array_filter(
            array_keys($composer['require'] ?? []),
            fn (int|string $packageName): bool => is_string($packageName) && str_starts_with($packageName, 'capell-app/'),
        ));

        sort($composerPackageRequirements);

        $manifestRequirements = $manifest['dependencies']['requires'] ?? [];
        sort($manifestRequirements);

        expect($composerPackageRequirements)->toBe($manifestRequirements);
    });
});
