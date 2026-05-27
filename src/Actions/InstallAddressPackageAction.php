<?php

declare(strict_types=1);

namespace Capell\Address\Actions;

use Capell\Address\Enums\ResourceEnum;
use Capell\Address\Support\AddressModelRegistrar;
use Capell\Admin\Actions\AssignPermissionsToRole;
use Capell\Core\Actions\Install\PublishPackageMigrationsAction;
use Capell\Core\Actions\Install\RunArtisanCommandAction;
use Capell\Core\Actions\Install\RunMigrationsAction;
use Capell\Core\Contracts\PackageLifecycleAction;
use Capell\Core\Contracts\ProgressReporter;
use Capell\Core\Data\PackageData;
use Capell\Core\Support\Install\NullProgressReporter;
use Filament\Facades\Filament;
use Illuminate\Support\Collection;
use Lorisleiva\Actions\Concerns\AsObject;

final class InstallAddressPackageAction implements PackageLifecycleAction
{
    use AsObject;

    public function handle(PackageData $package, array $arguments = [], ?ProgressReporter $reporter = null): void
    {
        $reporter ??= new NullProgressReporter;

        AddressModelRegistrar::register();

        Filament::getDefaultPanel()
            ->resources(array_map(fn (ResourceEnum $resourceEnum): string => $resourceEnum->value, ResourceEnum::cases()));

        AssignPermissionsToRole::run(resources: array_map(fn (ResourceEnum $resourceEnum): string => $resourceEnum->value, ResourceEnum::cases()));

        PublishPackageMigrationsAction::run(new Collection([$package->name => $package]), $reporter, true, false);
        RunMigrationsAction::run($reporter);

        RunArtisanCommandAction::run('vendor:publish', ['--tag' => 'blade-country-flags'], $reporter);

        if (! app()->runningUnitTests()) {
            RunArtisanCommandAction::run('filament:assets', [], $reporter);
        }

        $reporter->report('Capell Address installed successfully.');
    }
}
