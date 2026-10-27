<?php

declare(strict_types=1);

use Capell\Address\Filament\Resources\Addresses\Pages\ManageAddresses;
use Capell\Address\Filament\Resources\Countries\Pages\ManageCountries;
use Capell\Address\Models\Address;
use Capell\Address\Models\Country;
use Capell\Tests\Support\Concerns\CreatesAdminUser;
use Filament\Actions\Action;
use Filament\Actions\Contracts\HasActions;
use Filament\Tables\Contracts\HasTable;
use Illuminate\Database\Eloquent\Model;
use Livewire\Component;
use Livewire\Livewire;
use Livewire\Mechanisms\ExtendBlade\ExtendBlade;

uses(CreatesAdminUser::class);

/**
 * @param  class-string<Component>  $page
 * @param  Closure(): Model  $makeRecord
 */
it('renders translated delete consequences in the real admin surface', function (string $page, Closure $makeRecord, string $mode, string $actionName, string $translation, bool $force): void {
    test()->actingAsAdmin();
    $heading = __($translation . '.heading');
    $description = __($translation . '.description');

    expect($heading)->toBeString()->not->toBeEmpty()->not->toBe($translation . '.heading')
        ->and($description)->toBeString()->not->toBeEmpty()->not->toBe($translation . '.description');

    $record = $makeRecord();

    $parameters = $mode === 'page' ? ['record' => $record->getRouteKey()] : [];
    $component = Livewire::test($page, $parameters)->assertSuccessful();
    if ($force) {
        $record->delete();
        $surface = $component->instance();
        if ($mode === 'page') {
            throw_unless($surface instanceof HasActions, LogicException::class);
            $action = $surface->getAction($actionName);
        } else {
            throw_unless($surface instanceof HasTable, LogicException::class);
            $action = $surface->getTable()->getBulkAction($actionName);
        }

        throw_unless($action instanceof Action, LogicException::class);
        // Render the real action in its page context without rehydrating a trashed route record.
        $blade = resolve(ExtendBlade::class);
        $blade->startLivewireRendering($surface);
        try {
            $html = $action->record($record)->toModalHtmlable()->toHtml();
        } finally {
            $blade->endLivewireRendering();
        }

        expect($html)->toContain(e($heading), e($description));

        return;
    }

    if ($mode === 'page') {
        $component->mountAction($actionName);
    } else {
        if ($mode === 'bulk') {
            $component->mountTableBulkAction($actionName, [$record->getKey()]);
        } else {
            $component->mountTableAction($actionName, $record);
        }
    }

    $component->assertMountedActionModalSee($heading)->assertMountedActionModalSee($description);
})->with([
    'CountriesTable DeleteAction' => [ManageCountries::class, fn (): Model => Country::factory()->create(), 'table', 'delete', 'capell-address::country.delete_modal', false],
    'CountriesTable DeleteBulkAction' => [ManageCountries::class, fn (): Model => Country::factory()->create(), 'bulk', 'delete', 'capell-address::country.delete_bulk_modal', false],
    'CountriesTable ForceDeleteBulkAction' => [ManageCountries::class, fn (): Model => Country::factory()->create(), 'bulk', 'forceDelete', 'capell-address::country.force_delete_bulk_modal', true],
    'AddressesTable DeleteAction' => [ManageAddresses::class, fn (): Model => Address::factory()->create(), 'table', 'delete', 'capell-address::address.delete_modal', false],
    'AddressesTable DeleteBulkAction' => [ManageAddresses::class, fn (): Model => Address::factory()->create(), 'bulk', 'delete', 'capell-address::address.delete_bulk_modal', false],
    'AddressesTable ForceDeleteBulkAction' => [ManageAddresses::class, fn (): Model => Address::factory()->create(), 'bulk', 'forceDelete', 'capell-address::address.force_delete_bulk_modal', true],
]);
