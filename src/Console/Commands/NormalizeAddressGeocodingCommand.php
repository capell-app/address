<?php

declare(strict_types=1);

namespace Capell\Address\Console\Commands;

use Capell\Address\Actions\NormalizeAddressGeocodingAction;
use Capell\Address\Models\Address;
use Illuminate\Console\Command;

final class NormalizeAddressGeocodingCommand extends Command
{
    protected $signature = 'capell:address-geocode-normalize
        {--provider= : Optional geocoding provider key}
        {--dry-run : Report addresses that would be updated without writing}
        {--limit= : Maximum number of addresses to scan}';

    protected $description = 'Normalize address coordinates with available Address geocoding providers.';

    public function handle(NormalizeAddressGeocodingAction $normalizeGeocoding): int
    {
        $updated = 0;
        $scanned = 0;
        $limit = $this->limit();
        $provider = $this->provider();
        $dryRun = (bool) $this->option('dry-run');

        foreach (Address::query()->ordered()->cursor() as $address) {
            if (! $address instanceof Address) {
                continue;
            }

            if ($limit !== null && $scanned >= $limit) {
                break;
            }

            $scanned++;
            $result = $normalizeGeocoding->handle($address, $provider, $dryRun);

            if ($result->updated) {
                $updated++;
            }
        }

        $messageKey = $dryRun ? 'capell-address::geocoding.dry_run' : 'capell-address::geocoding.completed';

        $this->components->info((string) __($messageKey, [
            'updated' => $updated,
            'scanned' => $scanned,
        ]));

        return self::SUCCESS;
    }

    private function provider(): ?string
    {
        $provider = $this->option('provider');

        if (! is_string($provider) || trim($provider) === '') {
            return null;
        }

        return trim($provider);
    }

    private function limit(): ?int
    {
        $limit = $this->option('limit');

        if (! is_numeric($limit)) {
            return null;
        }

        return max(1, (int) $limit);
    }
}
