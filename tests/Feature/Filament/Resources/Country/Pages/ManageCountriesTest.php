<?php

declare(strict_types=1);

use BezhanSalleh\FilamentShield\Facades\FilamentShield;
use BezhanSalleh\FilamentShield\Support\Utils;
use Capell\Address\Actions\ReviewCountryUploadAction;
use Capell\Address\Filament\Components\Forms\CountrySelect;
use Capell\Address\Filament\Resources\Countries\Pages\ManageCountries;
use Capell\Address\Models\Country;
use Capell\Admin\Filament\Actions\CreateAction;
use Capell\Tests\Support\Concerns\CreatesAdminUser;
use Filament\Actions\EditAction;
use Filament\Actions\Testing\TestAction;
use Filament\Schemas\Schema;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Factories\Sequence;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Features\SupportFileUploads\FileUploadConfiguration;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\Features\SupportTesting\Testable;

use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertSoftDeleted;
use function Pest\Livewire\livewire;

use Spatie\Permission\Models\Permission;

uses(CreatesAdminUser::class)
    ->group('country');

function manageCountriesActionSchema(Testable $component): Schema
{
    $instance = $component->instance();
    throw_unless($instance instanceof ManageCountries, RuntimeException::class, 'Expected the ManageCountries component.');

    $schemaName = $instance->getMountedActionSchemaName();
    throw_unless(is_string($schemaName), RuntimeException::class, 'Expected a mounted action schema.');

    $schema = $instance->getSchema($schemaName);
    throw_unless($schema instanceof Schema, RuntimeException::class, 'Expected the mounted action schema.');

    return $schema;
}

beforeEach(function (): void {
    test()->actingAsAdmin();
});

test('offers import before advanced manual creation on an empty country index', function (): void {
    $component = livewire(ManageCountries::class)
        ->assertSuccessful()
        ->assertActionVisible('import')
        ->assertSee(__('capell-address::import.empty_description'))
        ->assertSee(__('capell-address::import.manual'))
        ->mountAction('import')
        ->assertActionMounted('import');
    expect(manageCountriesActionSchema($component)->toHtml())
        ->toContain(__('capell-address::import.format_help'));
});

test('requires a dataset before applying an import', function (): void {
    livewire(ManageCountries::class)
        ->callAction('import', ['dataset' => null])
        ->assertHasActionErrors(['dataset' => 'required']);

    expect(Country::query()->count())->toBe(0);
});

test('reviews uploaded countries without writes then explicitly applies every count', function (string $format): void {
    $france = Country::factory()->create(['name' => 'Old France', 'iso2' => 'FR', 'iso3' => 'FRA', 'status' => true]);
    $spain = Country::factory()->create(['name' => 'Spain', 'iso2' => 'ES', 'iso3' => 'ESP', 'status' => true]);
    $spain->delete();

    $germany = Country::factory()->create(['name' => 'Germany', 'iso2' => 'DE', 'iso3' => 'DEU', 'status' => true]);
    $contents = $format === 'json'
        ? '[{"name":"France","iso2":"FR","iso3":"FRA"},{"name":"Spain","iso2":"ES","iso3":"ESP"},{"name":"Canada","iso2":"CA","iso3":"CAN"},{"name":"","iso2":"ZZ","iso3":"ZZZ"}]'
        : "name,iso2,iso3\nFrance,FR,FRA\nSpain,ES,ESP\nCanada,CA,CAN\n,ZZ,ZZZ\n";

    $component = livewire(ManageCountries::class)
        ->mountAction('import')
        ->fillForm(['dataset' => UploadedFile::fake()->createWithContent('countries.' . $format, $contents), 'restore' => true, 'disableMissing' => true])
        ->goToNextWizardStep()
        ->assertHasNoFormErrors()
        ->assertSet('importDisabled', 1);

    foreach (['created', 'updated', 'restored', 'disabled', 'skipped'] as $count) {
        expect(manageCountriesActionSchema($component)->toHtml())
            ->toContain(__('capell-address::import.counts.' . $count, ['count' => 1]));
    }

    expect(manageCountriesActionSchema($component)->toHtml())
        ->toContain(__('capell-address::import.confirm_disable', ['count' => 1]));
    expect($france->refresh()->name)->toBe('Old France')
        ->and($spain->refresh()->trashed())->toBeTrue()
        ->and((bool) $germany->refresh()->status)->toBeTrue()
        ->and(Country::query()->where('iso2', 'CA')->exists())->toBeFalse();

    $component->fillForm(['confirmDisable' => true])
        ->callMountedAction()
        ->assertHasNoActionErrors()
        ->assertSet('importReview', null);

    expect($france->refresh()->name)->toBe('France')
        ->and($spain->refresh()->trashed())->toBeFalse()
        ->and((bool) $germany->refresh()->status)->toBeFalse()
        ->and(Country::query()->where('iso2', 'CA')->exists())->toBeTrue();
})->with(['json', 'csv']);

test('rejects a stale upload review before writing', function (string $change): void {
    $file = addressReviewUpload('[{"name":"Canada","iso2":"CA","iso3":"CAN"}]');
    $review = resolve(ReviewCountryUploadAction::class);
    $review->handle($file, false, false);

    $fingerprint = $review->fingerprint($file, false, false);

    if ($change === 'file') {
        $file = addressReviewUpload('[{"name":"France","iso2":"FR","iso3":"FRA"}]');
    } elseif ($change === 'database') {
        Country::factory()->create(['name' => 'Germany', 'iso2' => 'DE', 'iso3' => 'DEU']);
    }

    $before = Country::withTrashed()->orderBy('id')->get()->toArray();

    try {
        $review->apply($file, $change === 'disableMissing', $change === 'restore', $fingerprint, true);
        test()->fail('A stale review must not apply.');
    } catch (ValidationException $validationException) {
        expect($validationException->errors())->toBe(['dataset' => [__('capell-address::import.review_changed')]]);
    }

    expect(Country::withTrashed()->orderBy('id')->get()->toArray())->toBe($before);
})->with(['file', 'disableMissing', 'restore', 'database']);

test('requires separate confirmation for the exact number of disabled countries', function (): void {
    Country::factory()->create(['iso2' => 'DE', 'iso3' => 'DEU', 'status' => true]);
    Country::factory()->create(['iso2' => 'FR', 'iso3' => 'FRA', 'status' => true]);
    $file = addressReviewUpload('[{"name":"Canada","iso2":"CA","iso3":"CAN"}]');
    $review = resolve(ReviewCountryUploadAction::class);
    expect($review->handle($file, true, false)->disabled)->toBe(2);
    $fingerprint = $review->fingerprint($file, true, false);

    try {
        $review->apply($file, true, false, $fingerprint, false);
        test()->fail('Disabling countries requires confirmation.');
    } catch (ValidationException $validationException) {
        expect($validationException->errors())->toBe(['confirmDisable' => [__('capell-address::import.confirm_disable', ['count' => 2])]]);
    }

    expect(Country::query()->count())->toBe(2)
        ->and(Country::query()->enabled()->count())->toBe(2);

    $result = $review->apply($file, true, false, $fingerprint, true);
    expect($result->disabled)->toBe(2)
        ->and($result->created)->toBe(1)
        ->and(Country::query()->enabled()->pluck('iso2')->all())->toBe(['CA']);
});

test('rolls back all writes when duplicate rows make apply differ from review', function (): void {
    $germany = Country::factory()->create(['iso2' => 'DE', 'iso3' => 'DEU', 'status' => true]);
    $file = addressReviewUpload('[{"name":"Canada","iso2":"CA","iso3":"CAN"},{"name":"Canada","iso2":"CA","iso3":"CAN"}]');
    $review = resolve(ReviewCountryUploadAction::class);
    expect($review->handle($file, true, false)->created)->toBe(2);

    try {
        $review->apply($file, true, false, $review->fingerprint($file, true, false), true);
        test()->fail('Different applied counts must roll back.');
    } catch (ValidationException $validationException) {
        expect($validationException->errors())->toBe(['dataset' => [__('capell-address::import.conflicting_rows')]]);
    }

    expect(Country::query()->where('iso2', 'CA')->exists())->toBeFalse()
        ->and((bool) $germany->refresh()->status)->toBeTrue()
        ->and(Country::withTrashed()->count())->toBe(1);
});

test('rechecks permissions for both review and apply', function (bool $apply): void {
    $file = addressReviewUpload('[{"name":"Canada","iso2":"CA","iso3":"CAN"}]');
    $review = resolve(ReviewCountryUploadAction::class);
    $review->handle($file, false, false);

    $fingerprint = $review->fingerprint($file, false, false);
    test()->actingAsUser();

    expect(fn () => $apply
        ? $review->apply($file, false, false, $fingerprint, false)
        : $review->handle($file, false, false))->toThrow(AuthorizationException::class);
    expect(Country::query()->count())->toBe(0);
})->with([false, true]);

test('requires each country import permission independently', function (string $denied): void {
    $file = addressReviewUpload('[{"name":"Canada","iso2":"CA","iso3":"CAN"}]');
    $review = resolve(ReviewCountryUploadAction::class);
    $fingerprint = $review->fingerprint($file, false, true);
    test()->actingAsUser();
    $configuration = Utils::getConfig()->permissions;

    foreach (['create', 'update', 'restore_any'] as $ability) {
        if ($ability === $denied) {
            continue;
        }

        $permission = FilamentShield::defaultPermissionKeyBuilder(
            affix: $ability,
            separator: $configuration->separator,
            subject: 'Country',
            case: $configuration->case,
        );
        $this->authenticatedUser()->givePermissionTo(Permission::findOrCreate($permission, 'web'));
    }

    expect(fn () => $review->handle($file, false, true))->toThrow(AuthorizationException::class);
    expect(fn () => $review->apply($file, false, true, $fingerprint, false))->toThrow(AuthorizationException::class);
    expect(Country::withTrashed()->count())->toBe(0);
})->with(['create', 'update', 'restore_any']);

test('invalidates the staged review when an upload option changes', function (string $option): void {
    $component = livewire(ManageCountries::class)
        ->mountAction('import')
        ->fillForm(['dataset' => UploadedFile::fake()->createWithContent('countries.json', '[{"name":"Canada","iso2":"CA","iso3":"CAN"}]')])
        ->goToNextWizardStep()
        ->assertHasNoFormErrors();

    expect($component->get('importReview'))->toBeString()->not->toBeEmpty();
    $component->goToPreviousWizardStep()
        ->fillForm([$option => true])
        ->assertSet('importReview', null);
    expect(Country::query()->count())->toBe(0);
})->with(['restore', 'disableMissing']);

test('rejects drift during review instead of pairing old counts with a new fingerprint', function (): void {
    $country = Country::factory()->create(['name' => 'Germany', 'iso2' => 'DE', 'iso3' => 'DEU', 'status' => true]);
    $file = addressReviewUpload('[{"name":"Canada","iso2":"CA","iso3":"CAN"}]');
    $changed = false;
    Event::listen('eloquent.retrieved: ' . Country::class, function (Country $record) use (&$changed, $country): void {
        if (! $changed && $record->id === $country->id) {
            $changed = true;
            Country::query()->whereKey($country->id)->update(['status' => false]);
        }
    });

    expect(fn () => resolve(ReviewCountryUploadAction::class)->review($file, true, false))
        ->toThrow(ValidationException::class);
    expect($changed)->toBeTrue()
        ->and((bool) $country->refresh()->status)->toBeTrue()
        ->and(Country::query()->where('iso2', 'CA')->exists())->toBeFalse();
});

test('rejects an old disable confirmation after another active country is added', function (): void {
    Country::factory()->create(['iso2' => 'DE', 'iso3' => 'DEU', 'status' => true]);
    $file = addressReviewUpload('[{"name":"Canada","iso2":"CA","iso3":"CAN"}]');
    $review = resolve(ReviewCountryUploadAction::class);
    $snapshot = $review->review($file, true, false);
    expect($snapshot->counts->disabled)->toBe(1);
    Country::factory()->create(['iso2' => 'FR', 'iso3' => 'FRA', 'status' => true]);

    expect(fn () => $review->apply($file, true, false, $snapshot->fingerprint, true))
        ->toThrow(ValidationException::class);
    expect(Country::query()->enabled()->count())->toBe(2)
        ->and(Country::query()->where('iso2', 'CA')->exists())->toBeFalse();
});

test('applies an ISO correction without counting the matched country as missing', function (): void {
    $country = Country::factory()->create(['name' => 'United Kingdom', 'iso2' => 'GB', 'iso3' => 'GBR', 'status' => true]);
    $file = addressReviewUpload('[{"name":"United Kingdom","iso2":"UK","iso3":"GBR"}]');
    $review = resolve(ReviewCountryUploadAction::class);
    $snapshot = $review->review($file, true, false);
    expect($snapshot->counts->updated)->toBe(1)
        ->and($snapshot->counts->disabled)->toBe(0);
    $result = $review->apply($file, true, false, $snapshot->fingerprint, false);
    expect($result->updated)->toBe(1)
        ->and($result->disabled)->toBe(0)
        ->and($country->refresh()->iso2)->toBe('UK')
        ->and((bool) $country->status)->toBeTrue()
        ->and(Country::query()->count())->toBe(1);
});

test('rolls back earlier writes when a later country write throws', function (): void {
    $file = addressReviewUpload('[{"name":"Canada","iso2":"CA","iso3":"CAN"},{"name":"France","iso2":"FR","iso3":"FRA"}]');
    $review = resolve(ReviewCountryUploadAction::class);
    $snapshot = $review->review($file, false, false);
    $sawFirstWrite = false;
    Event::listen('eloquent.created: ' . Country::class, function (Country $country) use (&$sawFirstWrite): void {
        if ($country->iso2 === 'FR') {
            $sawFirstWrite = Country::query()->where('iso2', 'CA')->exists();
            throw new RuntimeException('Deliberate late import failure');
        }
    });

    expect(fn () => $review->apply($file, false, false, $snapshot->fingerprint, false))
        ->toThrow(RuntimeException::class, 'Deliberate late import failure');
    expect($sawFirstWrite)->toBeTrue()
        ->and(Country::withTrashed()->count())->toBe(0);
});

test('hides the import hint from country viewers', function (): void {
    $configuration = Utils::getConfig()->permissions;
    $permission = FilamentShield::defaultPermissionKeyBuilder(
        affix: 'view_any',
        separator: $configuration->separator,
        subject: 'Country',
        case: $configuration->case,
    );
    test()->actingAsUser();
    $this->authenticatedUser()->givePermissionTo(Permission::findOrCreate($permission, 'web'));
    $select = CountrySelect::make('country_id');

    expect(Gate::allows('viewAny', Country::class))->toBeTrue()
        ->and($select->getHintActions()[0]->isVisible())->toBeFalse();
});

test('reviews and applies storage-backed uploads without reading a local upload path', function (string $format, string $contents): void {
    // Model the remote upload API boundary: bytes are available from storage,
    // but no local path exists. The importer itself remains real.
    $file = Mockery::mock(TemporaryUploadedFile::class);
    $file->shouldReceive('get')->twice()->andReturn($contents);
    $file->shouldReceive('getClientOriginalExtension')->twice()->andReturn($format);
    $file->shouldNotReceive('getRealPath');
    $review = resolve(ReviewCountryUploadAction::class);

    $snapshot = $review->review($file, false, false);
    expect($snapshot->counts->created)->toBe(1)
        ->and(Country::query()->count())->toBe(0);

    $result = $review->apply($file, false, false, $snapshot->fingerprint, false);
    expect($result->created)->toBe(1)
        ->and(Country::query()->where('iso2', 'CA')->value('name'))->toBe('Canada');
})->with([
    'json' => ['json', '[{"name":"Canada","iso2":"CA","iso3":"CAN"}]'],
    'csv' => ['csv', "name,iso2,iso3\nCanada,CA,CAN\n"],
]);

function addressReviewUpload(string $contents): TemporaryUploadedFile
{
    $disk = 'address-review-' . Str::uuid();
    Storage::fake($disk);
    $upload = UploadedFile::fake()->createWithContent('countries.json', $contents);
    $name = TemporaryUploadedFile::generateHashNameWithOriginalNameEmbedded($upload);
    Storage::disk($disk)->put(FileUploadConfiguration::path($name, false), $contents);

    return new TemporaryUploadedFile($name, $disk);
}

test('can list countries', function (): void {
    $countries = Country::factory()->count(5)->create();

    livewire(ManageCountries::class)
        ->assertSuccessful()
        ->assertCountTableRecords($countries->count())
        ->assertCanSeeTableRecords($countries);
});

test('can search countries', function (): void {
    $countries = Country::factory()
        ->count(3)
        ->sequence(fn (Sequence $sequence): array => ['name' => sprintf('Country(%d)', $sequence->index)])
        ->create();

    $name = $countries->random()->name;

    livewire(ManageCountries::class)
        ->assertSuccessful()
        ->assertCountTableRecords(3)
        ->searchTable($name)
        ->assertCanSeeTableRecords($countries->where('name', $name))
        ->assertCanNotSeeTableRecords($countries->where('name', '!=', $name));
});

test('can sort countries', function (): void {
    $countries = Country::factory()->count(10)->create();
    $sorted = Country::query()->orderBy('name')->pluck('id');

    livewire(ManageCountries::class)
        ->assertSuccessful()
        ->assertCountTableRecords($countries->count())
        ->sortTable('name')
        ->assertCanSeeTableRecords($sorted, inOrder: true);
});

test('can sort countries by iso2', function (): void {
    $lastByIso2 = Country::factory()->create([
        'name' => 'Country A',
        'iso2' => 'ZZ',
    ]);
    $firstByIso2 = Country::factory()->create([
        'name' => 'Country B',
        'iso2' => 'AA',
    ]);

    livewire(ManageCountries::class)
        ->assertSuccessful()
        ->sortTable('iso2')
        ->assertCanSeeTableRecords([$firstByIso2, $lastByIso2], inOrder: true);
});

test('ignores country reordering because countries do not have an order column', function (): void {
    $countries = Country::factory()->count(2)->create();

    livewire(ManageCountries::class)
        ->assertSuccessful()
        ->call('reorderTable', $countries->pluck('id')->reverse()->values()->all())
        ->assertSuccessful();
});

test('can replicate country', function (): void {
    $country = Country::factory()->create();

    $copyName = $country->name . ' (copy)';
    $copyIso2 = strtoupper(fake()->unique()->lexify('??'));
    $copyIso3 = strtoupper(fake()->unique()->lexify('???'));

    livewire(ManageCountries::class)
        ->assertSuccessful()
        ->assertCountTableRecords(1);

    $replica = $country->replicate();
    $replica->name = $copyName;
    $replica->iso2 = $copyIso2;
    $replica->iso3 = $copyIso3;
    $replica->save();

    livewire(ManageCountries::class)
        ->assertSuccessful()
        ->assertCountTableRecords(2);

    assertDatabaseHas('countries', [
        'name' => $copyName,
        'iso2' => $copyIso2,
        'iso3' => $copyIso3,
        'language_id' => $country->language_id,
    ]);
});

test('can create country', function (): void {
    $country = Country::factory()->make();

    livewire(ManageCountries::class)
        ->assertSuccessful()
        ->assertCountTableRecords(0)
        ->callAction(
            CreateAction::class,
            [
                'name' => $country->name,
                'iso2' => $country->iso2,
                'iso3' => $country->iso3,
                'language_id' => $country->language_id,
                'meta' => $country->meta,
            ],
        )
        ->assertHasNoFormErrors()
        ->assertCountTableRecords(1);

    assertDatabaseHas('countries', [
        'name' => $country->name,
        'iso2' => $country->iso2,
        'iso3' => $country->iso3,
        'language_id' => $country->language_id,
    ]);
});

test('can not create country', function (): void {
    livewire(ManageCountries::class)
        ->assertSuccessful()
        ->callAction(
            CreateAction::class,
            data: [
                'name' => '',
                'iso2' => '',
                'iso3' => '',
            ],
        )
        ->assertHasFormErrors([
            'name' => ['required'],
            'iso2' => ['required'],
            'iso3' => ['required'],
        ])
        ->assertCountTableRecords(0);
});

test('can update country', function (): void {
    $country = Country::factory()->create();
    $newCountry = Country::factory()->make();

    livewire(ManageCountries::class)
        ->assertSuccessful()
        ->callAction(
            TestAction::make(EditAction::class)->table($country),
            data: [
                'name' => $newCountry->name,
                'iso2' => $newCountry->iso2,
                'iso3' => $newCountry->iso3,
                'language_id' => $newCountry->language_id,
                'meta' => $newCountry->meta,
            ],
        )
        ->assertHasNoFormErrors();

    expect($country->refresh())
        ->name->toBe($newCountry->name)
        ->iso2->toBe($newCountry->iso2)
        ->iso3->toBe($newCountry->iso3)
        ->language_id->toBe($newCountry->language_id);
});

test('can not update country', function (): void {
    $country = Country::factory()->create();

    livewire(ManageCountries::class)
        ->assertSuccessful()
        ->callAction(
            TestAction::make(EditAction::class)->table($country),
            data: [
                'name' => '',
                'iso2' => '',
                'iso3' => '',
            ],
        )
        ->assertHasFormErrors([
            'name' => ['required'],
            'iso2' => ['required'],
            'iso3' => ['required'],
        ]);
});

test('can delete country', function (): void {
    $country = Country::factory()->create();

    livewire(ManageCountries::class)
        ->assertSuccessful()
        ->assertCountTableRecords(1);

    $country->delete();

    livewire(ManageCountries::class)
        ->assertSuccessful()
        ->assertCountTableRecords(0);

    assertSoftDeleted($country, ['id' => $country->id]);
});

test('can group delete countries', function (): void {
    $countries = Country::factory()
        ->sequence(fn (Sequence $sequence): array => ['name' => 'test-' . $sequence->index])
        ->count(5)->create();

    livewire(ManageCountries::class)
        ->assertSuccessful()
        ->assertCountTableRecords(5);

    $countries->each->delete();

    foreach ($countries as $country) {
        assertSoftDeleted($country, ['id' => $country->id]);
    }
});
