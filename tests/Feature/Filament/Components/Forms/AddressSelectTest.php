<?php

declare(strict_types=1);

use Capell\Address\Filament\Components\Forms\AddressSelect;
use Capell\Address\Filament\Components\Forms\CountrySelect;
use Capell\Address\Models\Address;
use Capell\Core\Models\Site;
use Filament\Forms\Components\Select;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Schemas\Schema;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Auth\User;
use Illuminate\Support\Collection as SupportCollection;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;

it('searches addresses by persisted address line columns', function (): void {
    $matchingAddress = Address::factory()->create([
        'name' => 'Warehouse Address',
        'line1' => '10 Foundry Street',
        'line2' => 'Suite 500',
    ]);

    $otherAddress = Address::factory()->create([
        'name' => 'Office Address',
        'line1' => '99 Market Road',
        'line2' => 'Floor 2',
    ]);

    $results = AddressSelect::make('address_id')->getSearchResults('Suite 500');

    expect($results)
        ->toHaveKey($matchingAddress->getKey())
        ->not->toHaveKey($otherAddress->getKey());
});

it('loads address options labels and selected records for admin forms', function (): void {
    $firstAddress = Address::factory()->create([
        'name' => 'Primary Warehouse',
        'line1' => '10 Foundry Street',
        'line2' => 'Suite 500',
        'city' => 'Bristol',
    ]);
    $secondAddress = Address::factory()->create([
        'name' => 'Secondary Office',
        'line1' => '20 Market Street',
        'city' => 'Leeds',
    ]);

    $select = AddressSelect::make('address_id')
        ->optionsLimit(10);

    $options = $select->getOptions();
    $selectedRecord = evaluateAddressSelectCallback($select, 'getSelectedRecordUsing', [
        'state' => $firstAddress->getKey(),
    ]);
    $secondAddressLabel = evaluateAddressSelectCallback($select, 'getOptionLabelUsing', [
        'value' => (string) $secondAddress->getKey(),
    ]);

    expect($options)->toHaveKey($firstAddress->getKey())
        ->and($options)->toHaveKey($secondAddress->getKey())
        ->and($options[$firstAddress->getKey()])->toContain('10 Foundry Street', 'Suite 500', 'Bristol')
        ->and($secondAddressLabel)->toBe('Secondary Office')
        ->and($selectedRecord)->toBeInstanceOf(Address::class)
        ->and($selectedRecord?->is($firstAddress))->toBeTrue();
});

it('scopes address select results and selected records to the actor assigned sites', function (): void {
    $assignedAddress = Address::factory()->create([
        'name' => 'Assigned Warehouse',
        'line1' => '10 Scoped Street',
        'line2' => 'Suite 500',
    ]);
    $hiddenAddress = Address::factory()->create([
        'name' => 'Hidden Warehouse',
        'line1' => '99 Scoped Street',
        'line2' => 'Suite 500',
    ]);
    $assignedSite = Site::factory()->create([
        'meta' => ['address_id' => $assignedAddress->getKey()],
    ]);
    Site::factory()->create([
        'meta' => ['address_id' => $hiddenAddress->getKey()],
    ]);

    auth()->setUser(addressSelectSiteScopedUser(collect([(int) $assignedSite->getKey()])));

    $select = AddressSelect::make('address_id')->optionsLimit(10);
    $options = $select->getOptions();
    $results = $select->getSearchResults('Suite 500');

    expect($options)->toHaveKey($assignedAddress->getKey())
        ->not->toHaveKey($hiddenAddress->getKey())
        ->and($results)->toHaveKey($assignedAddress->getKey())
        ->not->toHaveKey($hiddenAddress->getKey())
        ->and(evaluateAddressSelectCallback($select, 'getSelectedRecordUsing', [
            'state' => $assignedAddress->getKey(),
        ]))->toBeInstanceOf(Address::class)
        ->and(fn (): mixed => evaluateAddressSelectCallback($select, 'getSelectedRecordUsing', [
            'state' => $hiddenAddress->getKey(),
        ]))->toThrow(ModelNotFoundException::class);
});

function evaluateAddressSelectCallback(AddressSelect $select, string $property, array $parameters): mixed
{
    $reflection = new ReflectionProperty(Select::class, $property);
    $closure = $reflection->getValue($select);

    expect($closure)->toBeInstanceOf(Closure::class);

    return $select->evaluate($closure, $parameters, [
        AddressSelect::class => $select,
    ]);
}

/**
 * @param  SupportCollection<int, int>  $assignedSiteIds
 */
function addressSelectSiteScopedUser(SupportCollection $assignedSiteIds): User
{
    $user = new class extends User
    {
        /** @use HasFactory<Factory<static>> */
        use HasFactory;

        /** @var SupportCollection<int, int> */
        public SupportCollection $assignedSiteIds;

        public function isGlobalAdmin(): bool
        {
            return false;
        }

        public function checkPermissionTo(string $permission): bool
        {
            return true;
        }

        /** @return SupportCollection<int, int> */
        public function getAssignedSiteIds(): SupportCollection
        {
            return $this->assignedSiteIds;
        }
    };
    $user->assignedSiteIds = $assignedSiteIds;

    return $user;
}

it('binds address and country option forms to their mounted action state', function (string $selectClass, string $operation): void {
    $component = new AddressOptionSchemaHarness;
    $schema = Schema::make($component)
        ->statePath('mountedActions.0.data')
        ->operation($operation);
    $select = $selectClass::make('selected_id');
    $optionForm = $operation === 'createOption'
        ? $select->withCreateForm()->getCreateOptionActionForm($schema)
        : $select->withEditForm()->getEditOptionActionForm($schema);

    expect($optionForm)->toBeInstanceOf(Schema::class);
    $optionForm->fill(['name' => 'Option draft']);
    expect(data_get($component->mountedActions, '0.data.name'))->toBe('Option draft');
})->with([
    'address create' => [AddressSelect::class, 'createOption'],
    'address edit' => [AddressSelect::class, 'editOption'],
    'country create' => [CountrySelect::class, 'createOption'],
    'country edit' => [CountrySelect::class, 'editOption'],
]);

final class AddressOptionSchemaHarness extends Component implements HasSchemas
{
    use InteractsWithSchemas;

    /** @var array<int, array<string, mixed>> */
    public array $mountedActions = [];
}

it('updates the selected address only when authorised', function (bool $allowed): void {
    $address = Address::factory()->create(['name' => 'Original address']);
    auth()->setUser(Capell\Tests\Fixtures\Models\User::factory()->create());
    Gate::before(static fn (User $user, string $ability): ?bool => $ability === 'update' ? $allowed : null);
    $select = AddressSelect::make('address_id')->withEditForm();
    $schema = Schema::make(new AddressOptionSchemaHarness)->model($address);
    $update = fn (): mixed => $select->evaluate($select->getUpdateOptionUsing(), [
        'data' => ['name' => 'Changed address'],
        'schema' => $schema,
        'form' => $schema,
    ]);

    if ($allowed) {
        $update();
        expect($address->fresh()->name)->toBe('Changed address');
    } else {
        expect($update)->toThrow(AuthorizationException::class)
            ->and($address->fresh()->name)->toBe('Original address');
    }
})->with(['allowed' => true, 'denied' => false]);
