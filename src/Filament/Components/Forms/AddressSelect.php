<?php

declare(strict_types=1);

namespace Capell\Address\Filament\Components\Forms;

use Capell\Address\Actions\CloneSharedAddressForMutationAction;
use Capell\Address\Actions\GetAddressNameAction;
use Capell\Address\Actions\GetAddressSelectRecordAction;
use Capell\Address\Actions\ListAddressOptionsAction;
use Capell\Address\Filament\Resources\Addresses\Schemas\AddressForm;
use Capell\Address\Models\Address;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Width;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\Gate;
use Override;

class AddressSelect extends Select
{
    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->label(__('capell-address::form.address'))
            ->searchable()
            ->options(
                fn (self $component): array => ListAddressOptionsAction::run(
                    search: null,
                    limit: $component->getOptionsLimit(),
                    fullAddressLabels: true,
                ),
            )
            ->getSelectedRecordUsing(
                fn (int $state): Address => GetAddressSelectRecordAction::run($state),
            )
            ->getOptionLabelUsing(
                fn (?string $value): ?string => GetAddressNameAction::run($value),
            )
            ->getSearchResultsUsing(
                fn (self $component, string $search): array => ListAddressOptionsAction::run(
                    search: $search,
                    limit: $component->getOptionsLimit(),
                ),
            );
    }

    public function withCreateForm(): self
    {
        return $this->createOptionForm(fn (Schema $configurator): Schema => AddressForm::configure($configurator))
            ->createOptionAction(
                fn (Action $action): Action => $action
                    ->modalHeading(__('capell-address::generic.address'))
                    ->model(Address::class)
                    ->successNotificationTitle(
                        fn (Action $action): string => __(
                            'capell-admin::notification.created_successfully',
                            ['name' => $this->modalHeadingText($action)],
                        ),
                    ),
            );
    }

    public function withEditForm(): self
    {
        return $this->fillEditOptionActionFormUsing(static function (Select $component): array {
            $record = $component->getSelectedRecord();

            return $record?->attributesToArray() ?? [];
        })
            ->editOptionForm(fn (Schema $configurator): Schema => AddressForm::configure($configurator))
            ->updateOptionUsing(static function (array $data, Schema $configurator): ?int {
                $record = $configurator->getRecord();

                if ($record instanceof Address) {
                    Gate::authorize('update', $record);

                    return (int) CloneSharedAddressForMutationAction::run($record, $data)->getKey();
                }

                return null;
            })
            ->editOptionAction(
                fn (Action $action): Action => $action
                    ->modalHeading(__('capell-address::generic.address'))
                    ->modalWidth(Width::ScreenMedium)
                    ->model(Address::class)
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
