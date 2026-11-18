<?php

declare(strict_types=1);

use Capell\Address\Models\Address;
use Capell\Address\Providers\AddressServiceProvider;
use Capell\Address\Support\Privacy\AddressPrivacyContributor;
use Capell\Core\Data\Privacy\PrivacySubject;
use Capell\Core\Enums\PrivacyErasureMode;
use Capell\Core\Facades\CapellCore;
use Capell\Core\Models\Site;
use Capell\Core\Support\Packages\InstalledRuntimeLifecycle;
use Capell\Core\Support\Privacy\PrivacySubjectRegistry;

it('declares privacy integration as optional on both package surfaces', function (): void {
    $root = dirname(__DIR__, 2);
    $composer = capell_json_file_array($root . '/composer.json');
    $manifest = capell_json_file_array($root . '/capell.json');

    expect(data_get($composer, 'suggest'))->toHaveKey('capell-app/privacy-center')
        ->and(data_get($composer, 'require'))->not->toHaveKey('capell-app/privacy-center')
        ->and(data_get($manifest, 'dependencies.supports'))->toContain('capell-app/privacy-center')
        ->and(data_get($manifest, 'dependencies.requires'))->not->toContain('capell-app/privacy-center');
});

it('exports and erases only the subject addresses through Core privacy registry', function (): void {
    CapellCore::forcePackageInstalled(AddressServiceProvider::$packageName);
    app()->instance(InstalledRuntimeLifecycle::class, new InstalledRuntimeLifecycle(app()));
    app()->forgetInstance(PrivacySubjectRegistry::class);
    app()->register(AddressServiceProvider::class, force: true);

    $site = Site::factory()->create();
    Address::factory()->create(['site_id' => $site->getKey(), 'line1' => 'Subject address']);
    $foreign = Address::factory()->create(['site_id' => Site::factory()->create()->getKey()]);
    $registry = resolve(PrivacySubjectRegistry::class);
    $subject = PrivacySubject::forModel($site);

    $export = $registry->export($subject);
    $result = $registry->erase($subject, PrivacyErasureMode::Delete);

    expect($registry->exporters()['address'] ?? null)->toBeInstanceOf(AddressPrivacyContributor::class)
        ->and($registry->erasers()['address'] ?? null)->toBeInstanceOf(AddressPrivacyContributor::class)
        ->and(data_get($export['address']->data, 'addresses.0.line1'))->toBe('Subject address')
        ->and($result->failures)->toBe([])
        ->and($result->receipts)->toHaveCount(1)
        ->and($result->receipts[0]->contributor)->toBe('address')
        ->and($result->receipts[0]->mode)->toBe(PrivacyErasureMode::Delete)
        ->and($result->receipts[0]->affectedRecords)->toBe(1)
        ->and(Address::query()->count())->toBe(1)
        ->and($foreign->fresh())->not->toBeNull()
        ->and(data_get($registry->export($subject)['address']->data, 'addresses', []))->toBe([]);
});
