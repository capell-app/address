<?php

declare(strict_types=1);

namespace Capell\Address\Providers;

use Capell\Address\Actions\BuildAddressPrivacyExportAction;
use Capell\Address\Actions\EnsureSiteOwnsAddressAction;
use Capell\Address\Actions\EraseAddressPrivacyDataAction;
use Capell\Address\Console\Commands\DemoCommand;
use Capell\Address\Console\Commands\FakerCommand;
use Capell\Address\Console\Commands\ImportCountriesCommand;
use Capell\Address\Console\Commands\InstallCommand;
use Capell\Address\Console\Commands\NormalizeAddressGeocodingCommand;
use Capell\Address\Enums\ConfiguratorTypeEnum;
use Capell\Address\Enums\ResourceEnum;
use Capell\Address\Filament\Configurators\Languages\DefaultLanguageConfigurator;
use Capell\Address\Filament\Resources\Sites\Schemas\Extenders\SiteSchemaExtender;
use Capell\Address\Models\Address;
use Capell\Address\Models\Country;
use Capell\Address\Policies\AddressPolicy;
use Capell\Address\Policies\CountryPolicy;
use Capell\Address\Support\AddressModelRegistrar;
use Capell\Address\Support\FlagIconRenderer;
use Capell\Address\Support\Language\FlagsService;
use Capell\Admin\Data\AdminSurfaceContributionData;
use Capell\Admin\Enums\AdminSurfaceContributionType;
use Capell\Admin\Enums\ConfiguratorTypeEnum as AdminConfiguratorTypeEnum;
use Capell\Admin\Enums\SchemaExtenderEnum;
use Capell\Admin\Facades\CapellAdmin;
use Capell\Admin\Filament\Plugin\CapellAdminPlugin;
use Capell\Admin\Support\AdminSurfaceContributionRegistry;
use Capell\Core\Data\VendorAssetData;
use Capell\Core\Facades\CapellCore;
use Capell\Core\Models\Site;
use Capell\Core\Support\Packages\AbstractPackageServiceProvider;
use Capell\PrivacyCenter\Support\PrivacySubjectEraserRegistry;
use Capell\PrivacyCenter\Support\PrivacySubjectExporterRegistry;
use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Gate;
use Override;
use Spatie\LaravelPackageTools\Package;

final class AddressServiceProvider extends AbstractPackageServiceProvider
{
    private const string ADMIN_FLAG_ICON_RENDERER_CONTRACT = \Capell\Admin\Contracts\Support\FlagIconRenderer::class;

    public static string $name = 'capell-address';

    public static string $packageName = 'capell-app/address';

    public function configurePackage(Package $package): void
    {
        $package->name(self::$name)
            ->hasViews(self::$name)
            ->hasCommands([
                DemoCommand::class,
                FakerCommand::class,
                ImportCountriesCommand::class,
                InstallCommand::class,
                NormalizeAddressGeocodingCommand::class,
            ])
            ->hasMigrations([
                '2026_05_10_190839_01_create_countries_table',
                '2026_05_10_190839_02_create_addresses_table',
                '2026_07_12_000001_add_address_ownership_and_encrypt_meta',
            ])
            ->hasTranslations();
    }

    #[Override]
    public function registeringPackage(): void
    {
        parent::registeringPackage();

        $this->app->booting(function (): void {
            if ($this->isPackageInstalled()) {
                $this->registerResources();
            }
        });
    }

    #[Override]
    protected function isPackageInstalled(): bool
    {
        return CapellCore::getPackage(self::$packageName)->isInstalled();
    }

    #[Override]
    protected function bootInstalledPackage(): self
    {
        return $this
            ->registerModels()
            ->registerPolicies()
            ->registerRelationships()
            ->registerPackageAssets()
            ->registerSupportServices()
            ->registerResources()
            ->registerConfigurators()
            ->registerLanguageConfigurator()
            ->registerSchemaExtenders()
            ->registerPrivacyCenterContributors()
            ->registerBladeComponents();
    }

    private function registerPackageAssets(): self
    {
        CapellCore::registerVendorAsset(
            VendorAssetData::tailwindSource('resources/views/**/*.blade.php', self::$packageName),
        );

        return $this;
    }

    private function registerSupportServices(): self
    {
        $this->app->singleton(FlagIconRenderer::class);
        $this->app->singleton(FlagsService::class);

        if (interface_exists(self::ADMIN_FLAG_ICON_RENDERER_CONTRACT)) {
            $this->app->singleton(self::ADMIN_FLAG_ICON_RENDERER_CONTRACT, FlagIconRenderer::class);
        }

        return $this;
    }

    private function registerSchemaExtender(string $tag, string $class): void
    {
        $this->app->singleton($class, fn (): object => new $class);
        $this->app->tag($class, $tag);
    }

    private function registerModels(): self
    {
        AddressModelRegistrar::register();

        return $this;
    }

    private function registerPolicies(): self
    {
        Gate::policy(Address::class, AddressPolicy::class);
        Gate::policy(Country::class, CountryPolicy::class);

        return $this;
    }

    private function registerConfigurators(): self
    {
        foreach (ConfiguratorTypeEnum::getAllConfigurators() as $type => $configurators) {
            foreach ($configurators as $configuratorClass) {
                CapellAdmin::contributeToAdminSurface(AdminSurfaceContributionData::configurator(
                    class: $configuratorClass,
                    group: $type,
                    name: $configuratorClass::getKey(),
                ));
            }
        }

        return $this;
    }

    private function registerLanguageConfigurator(): self
    {
        CapellAdmin::serving(function (): void {
            if (! Filament::getCurrentPanel()?->hasPlugin(CapellAdminPlugin::ID)) {
                return;
            }

            $registry = $this->app->make(AdminSurfaceContributionRegistry::class);
            $key = 'configurator:' . AdminConfiguratorTypeEnum::Language->value . ':' . DefaultLanguageConfigurator::getKey();
            $contributions = $registry->all()[AdminSurfaceContributionType::Configurator->value] ?? [];

            if (! isset($contributions[$key])) {
                return;
            }

            $registry->replace(AdminSurfaceContributionData::configurator(
                class: DefaultLanguageConfigurator::class,
                group: AdminConfiguratorTypeEnum::Language->value,
                name: DefaultLanguageConfigurator::getKey(),
            ));
        });

        return $this;
    }

    private function registerResources(): self
    {
        CapellAdmin::contributeToAdminSurface(AdminSurfaceContributionData::resource(
            class: ResourceEnum::Address->value,
            group: ResourceEnum::Address->name,
        ));
        CapellAdmin::contributeToAdminSurface(AdminSurfaceContributionData::resource(
            class: ResourceEnum::Country->value,
            group: ResourceEnum::Country->name,
        ));

        return $this;
    }

    private function registerSchemaExtenders(): self
    {
        $this->registerSchemaExtender(SchemaExtenderEnum::Site->value, SiteSchemaExtender::class);

        return $this;
    }

    private function registerBladeComponents(): self
    {
        Blade::componentNamespace('Capell\\Address\\View\\Components', 'capell-address');
        Blade::anonymousComponentNamespace('Capell\\Address\\View\\Components');

        return $this;
    }

    private function registerRelationships(): self
    {
        Site::saved(static fn (Site $site): ?Address => EnsureSiteOwnsAddressAction::run($site));

        Site::resolveRelationUsing(
            'address',
            fn (Site $model): BelongsTo => $model->belongsTo(Address::class, 'meta->address_id'),
        );

        Site::resolveRelationUsing(
            'country',
            fn (Site $model): HasOneThrough => $model->hasOneThrough(
                Country::class,
                Address::class,
                'id',
                'id',
                'meta->address_id',
                'country_id',
            ),
        );

        return $this;
    }

    private function registerPrivacyCenterContributors(): self
    {
        $eraser = PrivacySubjectEraserRegistry::class;
        $exporter = PrivacySubjectExporterRegistry::class;

        if ($this->app->bound($eraser)) {
            $this->app->make($eraser)->register('address', static fn (Model $subject): int => EraseAddressPrivacyDataAction::run($subject));
        }

        if ($this->app->bound($exporter)) {
            $this->app->make($exporter)->register('address', static fn (Model $subject): array => BuildAddressPrivacyExportAction::run($subject));
        }

        return $this;
    }
}
