<?php

declare(strict_types=1);

use Capell\Address\Bridges\AddressPrivacyBridge;
use Capell\Address\Models\Address;
use Capell\Core\Facades\CapellCore;
use Capell\Core\Models\Site;
use Capell\PrivacyCenter\Support\PrivacySubjectEraserRegistry;
use Capell\PrivacyCenter\Support\PrivacySubjectExporterRegistry;
use Illuminate\Container\Container;

it('declares privacy integration as optional on both package surfaces', function (): void {
    $root = dirname(__DIR__, 2);
    $composer = capell_json_file_array($root . '/composer.json');
    $manifest = capell_json_file_array($root . '/capell.json');

    expect(data_get($composer, 'suggest'))->toHaveKey('capell-app/privacy-center')
        ->and(data_get($composer, 'require'))->not->toHaveKey('capell-app/privacy-center')
        ->and(data_get($manifest, 'dependencies.supports'))->toContain('capell-app/privacy-center')
        ->and(data_get($manifest, 'dependencies.requires'))->not->toContain('capell-app/privacy-center');
});

it('does not resolve unavailable privacy registries', function (): void {
    $container = new Container;

    new AddressPrivacyBridge()->register($container);

    expect($container->getBindings())->toBe([])
        ->and($container->resolved(PrivacySubjectEraserRegistry::class))->toBeFalse()
        ->and($container->resolved(PrivacySubjectExporterRegistry::class))->toBeFalse();
});

it('does not register contributors when the optional package is unavailable', function (): void {
    CapellCore::partialMock()->shouldReceive('isPackageAvailable')
        ->with('capell-app/privacy-center')->once()->andReturnFalse();
    $container = new Container;
    $eraser = new PrivacySubjectEraserRegistry;
    $exporter = new PrivacySubjectExporterRegistry;
    $container->instance(PrivacySubjectEraserRegistry::class, $eraser);
    $container->instance(PrivacySubjectExporterRegistry::class, $exporter);

    new AddressPrivacyBridge()->register($container);

    expect($eraser->registeredKeys())->toBe([])
        ->and($exporter->registeredKeys())->toBe([]);
});

it('exports and erases only the subject addresses through the optional registries', function (): void {
    $container = new Container;
    $eraser = new PrivacySubjectEraserRegistry;
    $exporter = new PrivacySubjectExporterRegistry;
    $container->instance(PrivacySubjectEraserRegistry::class, $eraser);
    $container->instance(PrivacySubjectExporterRegistry::class, $exporter);
    new AddressPrivacyBridge()->register($container);

    $subject = Site::factory()->create();
    Address::factory()->create(['site_id' => $subject->getKey(), 'line1' => 'Subject address']);
    $foreign = Address::factory()->create(['site_id' => Site::factory()->create()->getKey()]);

    expect($container->make(PrivacySubjectEraserRegistry::class)->registeredKeys())->toBe(['address']);

    expect($container->make(PrivacySubjectExporterRegistry::class)->registeredKeys())->toBe(['address'])
        ->and(data_get($exporter->export($subject), 'address.addresses.0.line1'))->toBe('Subject address')
        ->and($eraser->anonymize($subject))->toBe(1)
        ->and(Address::query()->count())->toBe(1)
        ->and($foreign->fresh())->not->toBeNull();
});
