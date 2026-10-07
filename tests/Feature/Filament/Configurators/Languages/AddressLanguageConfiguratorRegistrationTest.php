<?php

declare(strict_types=1);

use Capell\Address\Filament\Configurators\Languages\DefaultLanguageConfigurator as AddressDefaultLanguageConfigurator;
use Capell\Address\Tests\AddressTestCase;
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
use PHPUnit\Framework\Attributes\Test;

final class AddressLanguageConfiguratorRegistrationTest extends AddressTestCase
{
    #[Test]
    public function does_not_fail_when_the_core_default_language_configurator_is_absent(): void
    {
        $registry = resolve(AdminSurfaceContributionRegistry::class);
        $registry->clear();
        Filament::setCurrentPanel(Filament::getPanel('custom-admin'));

        event(new ServingAdmin);

        expect($registry->configuratorsForGroup(ConfiguratorTypeEnum::Language->value))->toBe([]);
    }

    #[Test]
    public function explicitly_replaces_the_core_default_language_configurator_with_the_address_configurator(): void
    {
        $registry = resolve(AdminSurfaceContributionRegistry::class);
        $registry->clear();
        $registry->register(AdminSurfaceContributionData::configurator(
            class: CoreDefaultLanguageConfigurator::class,
            group: ConfiguratorTypeEnum::Language->value,
            name: CoreDefaultLanguageConfigurator::getKey(),
        ));

        Filament::setCurrentPanel(Filament::getPanel('secondary'));
        event(new ServingAdmin);

        expect($registry->configuratorsForGroup(ConfiguratorTypeEnum::Language->value))
            ->toBe([CoreDefaultLanguageConfigurator::getKey() => CoreDefaultLanguageConfigurator::class]);

        Filament::setCurrentPanel(Filament::getPanel('custom-admin'));
        event(new ServingAdmin);

        $key = 'configurator:' . ConfiguratorTypeEnum::Language->value . ':' . CoreDefaultLanguageConfigurator::getKey();
        $contribution = $registry->all()[AdminSurfaceContributionType::Configurator->value][$key] ?? null;

        expect($contribution)
            ->toBeInstanceOf(AdminSurfaceContributionData::class)
            ->class->toBe(AddressDefaultLanguageConfigurator::class)
            ->key->toBe($key)
            ->and($registry->configuratorsForGroup(ConfiguratorTypeEnum::Language->value))
            ->toBe([CoreDefaultLanguageConfigurator::getKey() => AddressDefaultLanguageConfigurator::class]);
    }

    #[Test]
    public function preserves_the_address_replacement_through_the_configurator_cache_lifecycle(): void
    {
        $registry = resolve(AdminSurfaceContributionRegistry::class);
        Filament::setCurrentPanel(Filament::getPanel('content'));

        $registry->register(AdminSurfaceContributionData::configurator(
            class: CoreDefaultLanguageConfigurator::class,
            group: ConfiguratorTypeEnum::Language->value,
            name: CoreDefaultLanguageConfigurator::getKey(),
        ));
        event(new ServingAdmin);

        try {
            $this->artisan('capell:admin-cache-configurators')->assertExitCode(0);
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
    }

    #[Override]
    protected function getEnvironmentSetUp(mixed $app): void
    {
        parent::getEnvironmentSetUp($app);

        // Panel routes and extender topology must be assembled before application boot.
        $app->booting(static function (): void {
            $panelRegistry = resolve(PanelRegistry::class);
            $panelRegistry->panels = [];
            $panelRegistry->defaultPanel = null;

            $panelRegistry->register(Panel::make()->id('custom-admin')->plugin(CapellAdminPlugin::make()));
            $panelRegistry->register(Panel::make()->id('secondary'));
            $panelRegistry->register(Panel::make()->id('content')->default()->plugin(CapellAdminPlugin::make()));
        });
    }
}
