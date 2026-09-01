<?php

declare(strict_types=1);

use Capell\Address\Filament\Configurators\Languages\DefaultLanguageConfigurator as AddressDefaultLanguageConfigurator;
use Capell\Admin\Data\AdminSurfaceContributionData;
use Capell\Admin\Enums\AdminSurfaceContributionType;
use Capell\Admin\Enums\ConfiguratorTypeEnum;
use Capell\Admin\Events\ServingAdmin;
use Capell\Admin\Facades\CapellAdmin;
use Capell\Admin\Filament\Configurators\Languages\DefaultLanguageConfigurator as CoreDefaultLanguageConfigurator;
use Capell\Admin\Filament\Plugin\CapellAdminPlugin;
use Capell\Admin\Support\AdminSurfaceContributionCache;
use Capell\Admin\Support\AdminSurfaceContributionRegistry;
use Filament\Facades\Filament;
use Filament\Panel;
use Filament\PanelRegistry;

it('does not fail when the core default language configurator is absent', function (): void {
    $registry = resolve(AdminSurfaceContributionRegistry::class);
    $registry->clear();
    Filament::setCurrentPanel(Panel::make()->id('custom-admin')->plugin(CapellAdminPlugin::make()));

    event(new ServingAdmin);

    expect($registry->configuratorsForGroup(ConfiguratorTypeEnum::Language->value))->toBe([]);
});

it('explicitly replaces the core default language configurator with the address configurator', function (): void {
    $registry = resolve(AdminSurfaceContributionRegistry::class);
    $registry->clear();
    $registry->register(AdminSurfaceContributionData::configurator(
        class: CoreDefaultLanguageConfigurator::class,
        group: ConfiguratorTypeEnum::Language->value,
        name: CoreDefaultLanguageConfigurator::getKey(),
    ));

    Filament::setCurrentPanel(Panel::make()->id('secondary'));
    event(new ServingAdmin);

    expect($registry->configuratorsForGroup(ConfiguratorTypeEnum::Language->value))
        ->toBe([CoreDefaultLanguageConfigurator::getKey() => CoreDefaultLanguageConfigurator::class]);

    Filament::setCurrentPanel(Panel::make()->id('custom-admin')->plugin(CapellAdminPlugin::make()));
    event(new ServingAdmin);

    $key = 'configurator:' . ConfiguratorTypeEnum::Language->value . ':' . CoreDefaultLanguageConfigurator::getKey();
    $contribution = $registry->all()[AdminSurfaceContributionType::Configurator->value][$key] ?? null;

    expect($contribution)
        ->toBeInstanceOf(AdminSurfaceContributionData::class)
        ->class->toBe(AddressDefaultLanguageConfigurator::class)
        ->key->toBe($key)
        ->and($registry->configuratorsForGroup(ConfiguratorTypeEnum::Language->value))
        ->toBe([CoreDefaultLanguageConfigurator::getKey() => AddressDefaultLanguageConfigurator::class]);
});

it('preserves the address replacement through the configurator cache lifecycle', function (): void {
    $registry = resolve(AdminSurfaceContributionRegistry::class);
    $panel = Panel::make()
        ->id('content')
        ->plugin(CapellAdminPlugin::make());
    $panelRegistry = resolve(PanelRegistry::class);
    $panelRegistry->panels = [$panel->getId() => $panel];
    $panelRegistry->defaultPanel = $panel;

    $registry->register(AdminSurfaceContributionData::configurator(
        class: CoreDefaultLanguageConfigurator::class,
        group: ConfiguratorTypeEnum::Language->value,
        name: CoreDefaultLanguageConfigurator::getKey(),
    ));
    event(new ServingAdmin);

    try {
        capell_artisan('capell:admin-cache-configurators')->assertExitCode(0);
        event(new ServingAdmin);
        $key = 'configurator:' . ConfiguratorTypeEnum::Language->value . ':' . CoreDefaultLanguageConfigurator::getKey();
        $contribution = $registry->all()[AdminSurfaceContributionType::Configurator->value][$key] ?? null;
        expect($contribution)
            ->toBeInstanceOf(AdminSurfaceContributionData::class)
            ->class
            ->toBe(AddressDefaultLanguageConfigurator::class);
        resolve(AdminSurfaceContributionCache::class)->cache();

        $cache = require CapellAdmin::getConfiguratorCachePath();

        expect($cache[AdminSurfaceContributionType::Configurator->value][$key]['class'])
            ->toBe(AddressDefaultLanguageConfigurator::class);

        $registry->clear();
        resolve(AdminSurfaceContributionCache::class)->restore();

        expect($registry->configuratorsForGroup(ConfiguratorTypeEnum::Language->value))
            ->toBe([CoreDefaultLanguageConfigurator::getKey() => AddressDefaultLanguageConfigurator::class]);
    } finally {
        CapellAdmin::clearCachedConfigurators();
        $registry->clear();
    }
});
