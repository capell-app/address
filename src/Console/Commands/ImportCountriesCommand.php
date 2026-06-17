<?php

declare(strict_types=1);

namespace Capell\Address\Console\Commands;

use Capell\Address\Actions\ImportCountriesAction;
use Capell\Address\Data\ImportCountriesResultData;
use Illuminate\Console\Command;
use InvalidArgumentException;

final class ImportCountriesCommand extends Command
{
    protected $signature = 'capell:address-countries-import
        {path : JSON or CSV country dataset path}
        {--dry-run : Report changes without writing them}
        {--disable-missing : Disable enabled countries not present in the dataset}
        {--restore : Restore soft-deleted countries when their ISO code appears in the dataset}';

    protected $description = 'Import or refresh countries from a JSON or CSV ISO dataset.';

    public function handle(ImportCountriesAction $importCountries): int
    {
        $path = $this->argument('path');

        if (! is_string($path) || trim($path) === '') {
            $this->components->error((string) __('capell-address::import.path_required'));

            return self::INVALID;
        }

        try {
            $result = $importCountries->handle(
                path: $path,
                dryRun: (bool) $this->option('dry-run'),
                disableMissing: (bool) $this->option('disable-missing'),
                restore: (bool) $this->option('restore'),
            );
        } catch (InvalidArgumentException $exception) {
            $this->components->error($exception->getMessage());

            return self::INVALID;
        }

        $this->outputResult($result);

        return self::SUCCESS;
    }

    private function outputResult(ImportCountriesResultData $result): void
    {
        $messageKey = $result->dryRun ? 'capell-address::import.dry_run' : 'capell-address::import.completed';

        $this->components->info((string) __($messageKey, [
            'changed' => $result->changed(),
        ]));

        $this->line((string) __('capell-address::import.counts.created', ['count' => $result->created]));
        $this->line((string) __('capell-address::import.counts.updated', ['count' => $result->updated]));
        $this->line((string) __('capell-address::import.counts.restored', ['count' => $result->restored]));
        $this->line((string) __('capell-address::import.counts.disabled', ['count' => $result->disabled]));
        $this->line((string) __('capell-address::import.counts.skipped', ['count' => $result->skipped]));
    }
}
