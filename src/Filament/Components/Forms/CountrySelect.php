<?php

declare(strict_types=1);

namespace Capell\Address\Filament\Components\Forms;

use Capell\Address\Actions\GetCountryNameAction;
use Capell\Address\Actions\ListCountryOptionsAction;
use Capell\Address\Enums\ResourceEnum;
use Capell\Address\Filament\Resources\Countries\Schemas\CountryForm;
use Capell\Address\Models\Country;
use Capell\Admin\Support\AdminSurfaceLookup;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Schemas\Schema;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\Gate;
use Override;

class CountrySelect extends Select
{
    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->label(__('capell-address::form.country'))
            ->helperText(fn (): ?string => Country::query()->enabled()->exists() ? null : (string) __('capell-address::import.no_active_countries'))
            ->hintAction(Action::make('importCountries')
                ->label(__('capell-address::import.action'))
                ->visible(function (): bool {
                    $resource = AdminSurfaceLookup::resource(ResourceEnum::Country);

                    return ! Country::query()->enabled()->exists()
                        && $resource::canViewAny()
                        && Gate::allows('create', Country::class)
                        && Gate::allows('update', new Country);
                })
                ->url(function (): string {
                    $resource = AdminSurfaceLookup::resource(ResourceEnum::Country);

                    return $resource::getUrl(parameters: ['action' => 'import']);
                }))
            ->searchable()
            ->options(
                fn (self $component): array => ListCountryOptionsAction::run(null, $component->getOptionsLimit()),
            )
            ->getOptionLabelUsing(
                fn (?string $value): ?string => GetCountryNameAction::run($value),
            )
            ->getSearchResultsUsing(
                fn (self $component, string $search): array => ListCountryOptionsAction::run($search, $component->getOptionsLimit()),
            );
    }

    public function withCreateForm(): self
    {
        return $this->createOptionForm(fn (Schema $configurator): Schema => CountryForm::configure($configurator)
            ->model(Country::class))
            ->createOptionAction(
                fn (Action $action): Action => $action
                    ->modalHeading(__('capell-address::form.country'))
                    ->model(Country::class)
                    ->successNotificationTitle(
                        fn (Action $action): string => __(
                            'capell-admin::notification.created_successfully',
                            ['name' => $this->modalHeadingText($action)],
                        ),
                    )
                    ->after(function (Action $action): void {
                        $action->success();
                    }),
            );
    }

    public function withEditForm(): self
    {
        return $this->fillEditOptionActionFormUsing(static function (Select $component): array {
            $record = $component->getSelectedRecord();

            return $record?->attributesToArray() ?? [];
        })
            ->editOptionForm(fn (Schema $configurator): Schema => CountryForm::configure($configurator))
            ->editOptionAction(
                fn (Action $action): Action => $action
                    ->modalHeading(__('capell-address::form.country'))
                    ->model(Country::class)
                    ->successNotificationTitle(
                        fn (Action $action): string => __(
                            'capell-admin::notification.updated_successfully',
                            ['name' => $this->modalHeadingText($action)],
                        ),
                    )
                    ->after(function (Action $action): void {
                        $action->success();
                    }),
            );
    }

    private function modalHeadingText(Action $action): string
    {
        $heading = $action->getModalHeading();

        return $heading instanceof Htmlable ? $heading->toHtml() : $heading;
    }
}
