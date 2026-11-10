<?php

declare(strict_types=1);

namespace Capell\Address\Tests\Support;

use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Livewire\Component;

final class AddressOptionSchemaHarness extends Component implements HasSchemas
{
    use InteractsWithSchemas;

    /** @var array<int, array<string, mixed>> */
    public array $mountedActions = [];
}
