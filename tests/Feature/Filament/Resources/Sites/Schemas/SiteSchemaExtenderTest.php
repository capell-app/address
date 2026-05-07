<?php

declare(strict_types=1);

use Capell\Address\Filament\Components\Forms\AddressSelect;
use Capell\Address\Filament\Resources\Sites\Schemas\Extenders\SiteSchemaExtender;
use Capell\Admin\Enums\PageTranslationSchemaHookEnum;
use Capell\Admin\Enums\SiteCreateWizardHookEnum;
use Capell\Core\Models\Site;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

it('preserves unchanged site schema extension points', function (): void {
    $extender = new SiteSchemaExtender;
    $schema = Mockery::mock(Schema::class);
    $site = Site::factory()->make();
    $relationManagers = ['existing-relation-manager'];
    $tabs = ['existing-tab'];

    expect($extender->extendRelationManagers($site, $relationManagers))->toBe($relationManagers)
        ->and($extender->extendTabs($schema, $tabs))->toBe($tabs)
        ->and($extender->extendTranslationComponentsForHook($schema, PageTranslationSchemaHookEnum::BeforeTitle))->toBe([])
        ->and($extender->extendCreateWizardComponentsForHook($schema, SiteCreateWizardHookEnum::PagesStepEnd))->toBe([]);
});

it('appends an address select to site meta details', function (): void {
    $schema = Mockery::mock(Schema::class);
    $schema->shouldReceive('isCreating')->once()->andReturnTrue();

    $components = (new SiteSchemaExtender)->extendSiteMetaDetailsComponents($schema, [
        TextInput::make('name'),
    ]);

    expect($components)
        ->toHaveCount(2)
        ->and($components[0])->toBeInstanceOf(TextInput::class)
        ->and($components[1])->toBeInstanceOf(AddressSelect::class)
        ->and($components[1]->getName())->toBe('address_id')
        ->and($components[1]->getColumnSpan())->toBe(['default' => 'full']);
});
