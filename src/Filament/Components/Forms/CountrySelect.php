<?php

declare(strict_types=1);

namespace Capell\Address\Filament\Components\Forms;

use Capell\Address\Actions\GetCountryNameAction;
use Capell\Address\Actions\ListCountryOptionsAction;
use Capell\Address\Filament\Resources\Countries\Schemas\CountryForm;
use Capell\Address\Models\Country;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Schemas\Schema;
use Illuminate\Contracts\Support\Htmlable;
use Override;

class CountrySelect extends Select
{
    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->label(__('capell-address::form.country'))
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
                    ->modalHeading(__('capell-admin::generic.language'))
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
