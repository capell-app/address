<?php

declare(strict_types=1);

namespace Capell\Address\Console\Commands;

use Capell\Address\Actions\RehashAddressBlindIndexesAction;
use Illuminate\Console\Command;

final class RehashAddressBlindIndexesCommand extends Command
{
    protected $signature = 'capell:address-blind-index-rehash
        {--chunk=200 : Number of addresses to process per batch}';

    protected $description = 'Recompute address lookup indexes with the configured blind-index key.';

    public function handle(): int
    {
        $chunk = $this->option('chunk');
        $chunkSize = is_numeric($chunk) ? max(1, (int) $chunk) : 200;

        $changed = RehashAddressBlindIndexesAction::run($chunkSize);

        $this->components->info((string) __('capell-address::blind_index.rehashed', ['changed' => $changed]));

        return self::SUCCESS;
    }
}
