<?php

declare(strict_types=1);

namespace Capell\Address\Bridges;

use Capell\Address\Actions\BuildAddressPrivacyExportAction;
use Capell\Address\Actions\EraseAddressPrivacyDataAction;
use Capell\Core\Facades\CapellCore;
use Capell\PrivacyCenter\Support\PrivacySubjectEraserRegistry;
use Capell\PrivacyCenter\Support\PrivacySubjectExporterRegistry;
use Illuminate\Contracts\Container\Container;
use Illuminate\Database\Eloquent\Model;

final class AddressPrivacyBridge
{
    public function register(Container $container): void
    {
        if (! CapellCore::isPackageAvailable('capell-app/privacy-center')) {
            return;
        }

        if ($container->bound(PrivacySubjectEraserRegistry::class)) {
            $container->make(PrivacySubjectEraserRegistry::class)
                ->register('address', static fn (Model $subject): int => EraseAddressPrivacyDataAction::run($subject));
        }

        if ($container->bound(PrivacySubjectExporterRegistry::class)) {
            $container->make(PrivacySubjectExporterRegistry::class)
                ->register('address', static fn (Model $subject): array => BuildAddressPrivacyExportAction::run($subject));
        }
    }
}
