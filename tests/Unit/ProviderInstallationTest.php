<?php

declare(strict_types=1);

use Capell\Address\Actions\EnsureSiteOwnsAddressAction;
use Capell\Address\Models\Address;
use Capell\Address\Providers\AddressServiceProvider;
use Capell\Admin\Contracts\Extenders\SiteSchemaExtender;
use Capell\Admin\Events\ServingAdmin;
use Capell\Admin\Macros\Filament\SchemaMacro;
use Capell\Core\Models\Site;
use Capell\Tests\Support\PackageInstallationTestCase;
use Filament\Forms\Components\Field;
use Filament\Schemas\Schema;
use Illuminate\Events\Dispatcher;
use Illuminate\Foundation\Application;

it('activates installed runtime once after metadata has already booted', function (): void {
    /** @return array{extenders: list<class-string<SiteSchemaExtender>>, fields: list<string>, saved: int, serving: int} */
    $snapshot = static function (Application $app): array {
        Schema::mixin(new SchemaMacro);
        $schema = Schema::make()->operation('edit');
        $extenders = [];
        $components = [];

        foreach ($app->tagged(SiteSchemaExtender::TAG) as $extender) {
            throw_unless($extender instanceof SiteSchemaExtender, RuntimeException::class);
            $extenders[] = $extender::class;
            $components = $extender->extendSiteMetaDetailsComponents($schema, $components);
        }

        $fields = [];
        foreach ($components as $component) {
            if ($component instanceof Field && $component->getName() === 'address_id') {
                $fields[] = $component->getName();
            }
        }

        $saved = 0;
        $action = $app->make(EnsureSiteOwnsAddressAction::class);
        $mock = Mockery::mock($action);
        $mock->shouldReceive('handle')->andReturnUsing(static function (Site $site) use (&$saved): ?Address {
            $saved++;

            return null;
        });
        $app->instance(EnsureSiteOwnsAddressAction::class, $mock);

        try {
            $site = new Site;
            $site->setRawAttributes(['id' => 1]);
            $app->make(Dispatcher::class)->dispatch('eloquent.saved: ' . Site::class, $site);
        } finally {
            $app->instance(EnsureSiteOwnsAddressAction::class, $action);
        }

        $serving = 0;
        $listeners = $app->make(Dispatcher::class)->getRawListeners()[ServingAdmin::class] ?? [];
        throw_unless(is_array($listeners), RuntimeException::class);
        foreach ($listeners as $listener) {
            if ($listener instanceof Closure && new ReflectionFunction($listener)->getClosureThis() instanceof AddressServiceProvider) {
                $serving++;
            }
        }

        return ['extenders' => $extenders, 'fields' => $fields, 'saved' => $saved, 'serving' => $serving];
    };

    $fresh = $snapshot($this->app);
    expect($fresh['extenders'])->toBe([Capell\Address\Filament\Resources\Sites\Schemas\Extenders\SiteSchemaExtender::class])
        ->and($fresh['fields'])->toBe(['address_id'])
        ->and($fresh['saved'])->toBe(1)
        ->and($fresh['serving'])->toBe(1);

    PackageInstallationTestCase::assertInProcessInstallation('address', static function (Application $app, Closure $refresh) use ($snapshot, $fresh): void {
        expect(iterator_to_array($app->tagged(SiteSchemaExtender::TAG)))->toBe([]);
        $refresh();
        expect($snapshot($app))->toBe($fresh);
        $listeners = $app->make(Dispatcher::class)->getRawListeners();
        $refresh();
        expect($snapshot($app))->toBe($fresh)
            ->and($app->make(Dispatcher::class)->getRawListeners())->toBe($listeners);
    });
});
