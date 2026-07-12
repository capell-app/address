<?php

declare(strict_types=1);

namespace Capell\Address\Actions;

use Capell\Address\Data\ImportCountriesResultData;
use Capell\Address\Models\Country;
use Illuminate\Support\Facades\File;
use InvalidArgumentException;
use JsonException;
use Lorisleiva\Actions\Concerns\AsAction;
use RuntimeException;

/**
 * @method static ImportCountriesResultData run(string $path, bool $dryRun = false, bool $disableMissing = false, bool $restore = false)
 */
final class ImportCountriesAction
{
    use AsAction;

    public function handle(
        string $path,
        bool $dryRun = false,
        bool $disableMissing = false,
        bool $restore = false,
    ): ImportCountriesResultData {
        if (! File::isFile($path)) {
            throw new InvalidArgumentException(sprintf('Country dataset [%s] does not exist.', $path));
        }

        $created = 0;
        $updated = 0;
        $skipped = 0;
        $restored = 0;
        /** @var list<string> $importedIso2 */
        $importedIso2 = [];

        foreach ($this->rows($path) as $row) {
            $countryData = $this->countryData($row);

            if ($countryData === null) {
                $skipped++;

                continue;
            }

            $importedIso2[] = $countryData['iso2'];

            $country = $this->existingCountry($countryData['iso2'], $countryData['iso3'], $restore);

            if (! $country instanceof Country) {
                $created++;

                if (! $dryRun) {
                    Country::query()->create($countryData + [
                        'status' => true,
                    ]);
                }

                continue;
            }

            $wasTrashed = $country->trashed();
            $country->forceFill($countryData + [
                'status' => true,
            ]);

            if (! $country->isDirty() && ! $wasTrashed) {
                $skipped++;

                continue;
            }

            if ($wasTrashed) {
                $restored++;
            } else {
                $updated++;
            }

            if (! $dryRun) {
                if ($wasTrashed) {
                    $country->restore();
                }

                $country->save();
            }
        }

        $disabled = $this->disableMissingCountries(array_values(array_unique($importedIso2)), $dryRun, $disableMissing);

        return new ImportCountriesResultData(
            created: $created,
            updated: $updated,
            skipped: $skipped,
            disabled: $disabled,
            restored: $restored,
            dryRun: $dryRun,
        );
    }

    /**
     * @return iterable<array<string, mixed>>
     */
    private function rows(string $path): iterable
    {
        return match (strtolower(pathinfo($path, PATHINFO_EXTENSION))) {
            'json' => $this->jsonRows($path),
            'csv' => $this->csvRows($path),
            default => throw new InvalidArgumentException('Country dataset must be a JSON or CSV file.'),
        };
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function jsonRows(string $path): array
    {
        try {
            $data = json_decode((string) File::get($path), associative: true, flags: JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new InvalidArgumentException('Country JSON dataset could not be decoded.', previous: $exception);
        }

        if (
            is_array($data)
            && array_key_exists('countries', $data)
            && is_array($data['countries'])
        ) {
            $data = $data['countries'];
        }

        if (! is_array($data)) {
            throw new InvalidArgumentException('Country JSON dataset must contain an array of country rows.');
        }

        return array_values(array_map(
            static fn (array $row): array => $row,
            array_filter(
                $data,
                static fn (mixed $row): bool => is_array($row),
            ),
        ));
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function csvRows(string $path): array
    {
        $stream = fopen($path, 'r');

        throw_if($stream === false, RuntimeException::class, 'Unable to open country CSV dataset.');

        $headers = fgetcsv($stream);

        if ($headers === false) {
            fclose($stream);

            return [];
        }

        $headers = array_map(
            static fn (?string $header): string => strtolower(trim((string) $header)),
            $headers,
        );
        $rows = [];

        while (($values = fgetcsv($stream)) !== false) {
            $row = [];

            foreach ($headers as $index => $header) {
                $row[$header] = $values[$index] ?? null;
            }

            $rows[] = $row;
        }

        fclose($stream);

        return $rows;
    }

    /**
     * @param  array<string, mixed>  $row
     * @return array{name: string, iso2: string, iso3: string}|null
     */
    private function countryData(array $row): ?array
    {
        $name = $this->stringValue($row['name'] ?? $row['country'] ?? null);
        $iso2 = strtoupper($this->stringValue($row['iso2'] ?? $row['alpha2'] ?? $row['alpha_2'] ?? null));
        $iso3 = strtoupper($this->stringValue($row['iso3'] ?? $row['alpha3'] ?? $row['alpha_3'] ?? null));

        if ($name === '' || strlen($iso2) !== 2 || strlen($iso3) !== 3) {
            return null;
        }

        return [
            'name' => $name,
            'iso2' => $iso2,
            'iso3' => $iso3,
        ];
    }

    private function existingCountry(string $iso2, string $iso3, bool $restore): ?Country
    {
        $query = Country::query();

        if ($restore) {
            $query->withTrashed();
        }

        $country = (clone $query)->where('iso2', $iso2)->first();

        if ($country instanceof Country) {
            return $country;
        }

        return $query->where('iso3', $iso3)->first();
    }

    /**
     * @param  list<string>  $importedIso2
     */
    private function disableMissingCountries(array $importedIso2, bool $dryRun, bool $disableMissing): int
    {
        if (! $disableMissing || $importedIso2 === []) {
            return 0;
        }

        $query = Country::query()
            ->whereNotNull('iso2')
            ->whereNotIn('iso2', $importedIso2)
            ->where('status', true);

        $count = $query->count();

        if (! $dryRun && $count > 0) {
            $query->update(['status' => false]);
        }

        return $count;
    }

    private function stringValue(mixed $value): string
    {
        return is_string($value) ? trim($value) : '';
    }
}
