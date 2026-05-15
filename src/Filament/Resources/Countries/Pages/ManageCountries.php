<?php

declare(strict_types=1);

namespace Capell\Address\Filament\Resources\Countries\Pages;

use Capell\Address\Enums\ResourceEnum;
use Capell\Admin\Support\AdminSurfaceLookup;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;
use Override;

class ManageCountries extends ManageRecords
{
    #[Override]
    public static function getResource(): string
    {
        return AdminSurfaceLookup::resource(ResourceEnum::Country);
    }

    protected function getActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
