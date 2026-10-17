<?php

declare(strict_types=1);

use Capell\Address\Filament\Configurators\Languages\DefaultLanguageConfigurator;
use Capell\Tests\Packages\Fixtures\PackageSurfaceContractSchemaHarness;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Schema;

it('offers flag choices in the configured language form', function (): void {
    $schema = Schema::make(new PackageSurfaceContractSchemaHarness)->operation('edit');
    $schema->components(array_map(static function (mixed $component): Component {
        if (! $component instanceof Component) {
            throw new RuntimeException('The configured form must contain schema components.');
        }

        return $component;
    }, (new DefaultLanguageConfigurator)->make($schema)));
    $field = collect($schema->getFlatComponents(withHidden: true))
        ->first(fn (mixed $component): bool => $component instanceof Select && $component->getName() === 'flag');

    expect($field)->toBeInstanceOf(Select::class);
    assert($field instanceof Select);
    expect($field->getOptions())->toHaveKey('gb');
});
