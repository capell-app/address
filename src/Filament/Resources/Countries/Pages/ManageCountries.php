<?php

declare(strict_types=1);

namespace Capell\Address\Filament\Resources\Countries\Pages;

use Capell\Address\Actions\ReviewCountryUploadAction;
use Capell\Address\Enums\ResourceEnum;
use Capell\Address\Models\Country;
use Capell\Admin\Support\AdminSurfaceLookup;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\FileUpload;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ManageRecords;
use Filament\Schemas\Components\Text;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Components\Wizard\Step;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Locked;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Override;

class ManageCountries extends ManageRecords
{
    #[Locked]
    public ?string $importReview = null;

    #[Locked]
    public string $importSummary = '';

    #[Locked]
    public int $importDisabled = 0;

    #[Override]
    public static function getResource(): string
    {
        return AdminSurfaceLookup::resource(ResourceEnum::Country);
    }

    public function importAction(): Action
    {
        return Action::make('import')
            ->label(__('capell-address::import.action'))
            ->visible(fn (): bool => Gate::allows('create', Country::class) && Gate::allows('update', new Country))
            ->mountUsing(function (Schema $schema): void {
                $schema->fill();
                $this->importReview = null;
                $this->importSummary = '';
                $this->importDisabled = 0;
            })
            ->modalSubmitActionLabel(__('capell-address::import.apply'))
            ->steps([
                Step::make(__('capell-address::import.upload'))
                    ->schema([
                        FileUpload::make('dataset')->label(__('capell-address::import.dataset'))
                            ->helperText(__('capell-address::import.format_help'))
                            ->acceptedFileTypes(['application/json', 'text/csv', 'text/plain'])
                            ->maxSize(2048)->storeFiles(false)->required()->live()
                            ->afterStateUpdated(fn (): null => $this->importReview = null),
                        Checkbox::make('restore')->label(__('capell-address::import.restore'))
                            ->live()->afterStateUpdated(fn (): null => $this->importReview = null),
                        Checkbox::make('disableMissing')->label(__('capell-address::import.disable_missing'))
                            ->live()->afterStateUpdated(fn (): null => $this->importReview = null),
                    ])
                    ->afterValidation(function (Get $get, Set $set): void {
                        $set('confirmDisable', false);
                        $file = $get('dataset');
                        if (is_array($file)) {
                            $file = reset($file);
                        }

                        if (! $file instanceof TemporaryUploadedFile) {
                            throw ValidationException::withMessages(['dataset' => __('capell-address::import.invalid_file')]);
                        }

                        $review = resolve(ReviewCountryUploadAction::class);
                        $this->importReview = null;
                        $snapshot = $review->review($file, (bool) $get('disableMissing'), (bool) $get('restore'));
                        $result = $snapshot->counts;
                        $this->importDisabled = $result->disabled;
                        $this->importSummary = implode(' · ', array_map(
                            fn (string $key): string => __('capell-address::import.counts.' . $key, ['count' => $result->{$key}]),
                            ['created', 'updated', 'restored', 'disabled', 'skipped'],
                        ));
                        $this->importReview = $snapshot->fingerprint;
                    }),
                Step::make(__('capell-address::import.review'))
                    ->schema([
                        Text::make(fn (): string => $this->importSummary),
                        Text::make(__('capell-address::import.review_help')),
                        Checkbox::make('confirmDisable')
                            ->label(fn (): string => __('capell-address::import.confirm_disable', ['count' => $this->importDisabled]))
                            ->visible(fn (): bool => $this->importDisabled > 0)
                            ->accepted(fn (): bool => $this->importDisabled > 0),
                    ]),
            ])
            ->action(function (array $data): void {
                $file = $data['dataset'];
                if (! $file instanceof TemporaryUploadedFile) {
                    throw ValidationException::withMessages(['dataset' => __('capell-address::import.invalid_file')]);
                }

                $review = resolve(ReviewCountryUploadAction::class);
                $disable = (bool) ($data['disableMissing'] ?? false);
                $restore = (bool) ($data['restore'] ?? false);
                try {
                    $result = $review->apply($file, $disable, $restore, $this->importReview, (bool) ($data['confirmDisable'] ?? false));
                } catch (ValidationException $validationException) {
                    Notification::make()->danger()->title(__('capell-address::import.apply_failed'))->body(implode(' ', $validationException->validator->errors()->all()))->send();

                    throw $validationException;
                }

                Notification::make()->success()->title(__('capell-address::import.completed', ['changed' => $result->changed()]))->send();
                $this->importReview = null;
                $this->resetTable();
            });
    }

    #[Override]
    protected function getActions(): array
    {
        return [
            $this->importAction(),
            CreateAction::make()->label(__('capell-address::import.manual'))->color('gray'),
        ];
    }
}
