<?php

declare(strict_types=1);

namespace Capell\Address\Actions;

use Capell\Address\Data\CountryImportReviewData;
use Capell\Address\Data\ImportCountriesResultData;
use Capell\Address\Models\Country;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use RuntimeException;

final class ReviewCountryUploadAction
{
    public function apply(TemporaryUploadedFile $file, bool $disableMissing, bool $restore, ?string $fingerprint, bool $confirmDisable): ImportCountriesResultData
    {
        $this->authorize($restore);

        try {
            return $this->withUpload($file, fn (string $path, string $format, string $hash): ImportCountriesResultData => (new Country)->getConnection()->transaction(function () use ($path, $format, $hash, $disableMissing, $restore, $fingerprint, $confirmDisable): ImportCountriesResultData {
                $review = $this->reviewSnapshot($path, $format, $hash, $disableMissing, $restore);

                if ($fingerprint === null || ! hash_equals($fingerprint, $review->fingerprint)) {
                    throw ValidationException::withMessages(['dataset' => __('capell-address::import.review_changed')]);
                }

                $preview = $review->counts;

                if ($preview->disabled > 0 && ! $confirmDisable) {
                    throw ValidationException::withMessages(['confirmDisable' => __('capell-address::import.confirm_disable', ['count' => $preview->disabled])]);
                }

                $result = $this->importSnapshot($path, $format, $disableMissing, $restore, false);

                foreach (['created', 'updated', 'restored', 'disabled', 'skipped'] as $count) {
                    if ($result->{$count} !== $preview->{$count}) {
                        throw ValidationException::withMessages(['dataset' => __('capell-address::import.conflicting_rows')]);
                    }
                }

                return $result;
            }));
        } catch (QueryException) {
            throw ValidationException::withMessages(['dataset' => __('capell-address::import.conflicting_rows')]);
        }
    }

    public function authorize(bool $restore): void
    {
        Gate::authorize('create', Country::class);
        Gate::authorize('update', new Country);

        if ($restore) {
            Gate::authorize('restoreAny', Country::class);
        }
    }

    public function fingerprint(TemporaryUploadedFile $file, bool $disableMissing, bool $restore): string
    {
        return $this->review($file, $disableMissing, $restore)->fingerprint;
    }

    public function review(TemporaryUploadedFile $file, bool $disableMissing, bool $restore): CountryImportReviewData
    {
        $this->authorize($restore);

        return $this->withUpload($file, fn (string $path, string $format, string $hash): CountryImportReviewData => $this->reviewSnapshot($path, $format, $hash, $disableMissing, $restore));
    }

    public function handle(TemporaryUploadedFile $file, bool $disableMissing, bool $restore, bool $dryRun = true): ImportCountriesResultData
    {
        $this->authorize($restore);

        return $this->withUpload($file, fn (string $path, string $format): ImportCountriesResultData => $this->importSnapshot($path, $format, $disableMissing, $restore, $dryRun));
    }

    private function reviewSnapshot(string $path, string $format, string $hash, bool $disableMissing, bool $restore): CountryImportReviewData
    {
        return (new Country)->getConnection()->transaction(function () use ($path, $format, $hash, $disableMissing, $restore): CountryImportReviewData {
            // Compare current reads around the dry run to reject visible drift.
            // Cross-connection guarantees depend on the database isolation level.
            $before = $this->stateFingerprint($hash, $format, $disableMissing, $restore);
            $counts = $this->importSnapshot($path, $format, $disableMissing, $restore, true);

            if (! hash_equals($before, $this->stateFingerprint($hash, $format, $disableMissing, $restore))) {
                throw ValidationException::withMessages(['dataset' => __('capell-address::import.review_changed')]);
            }

            return new CountryImportReviewData($counts, hash('sha256', serialize([$before, $counts])));
        });
    }

    private function stateFingerprint(string $hash, string $format, bool $disableMissing, bool $restore): string
    {
        return hash('sha256', serialize([
            $hash, $format, $disableMissing, $restore,
            Country::withTrashed()->orderBy('id')->lockForUpdate()->get()->map->getRawOriginal()->all(),
        ]));
    }

    private function importSnapshot(string $path, string $format, bool $disableMissing, bool $restore, bool $dryRun): ImportCountriesResultData
    {
        try {
            return resolve(ImportCountriesAction::class)->handle(
                $path,
                $dryRun,
                $disableMissing,
                $restore,
                $format,
            );
        } catch (InvalidArgumentException) {
            throw ValidationException::withMessages(['dataset' => __('capell-address::import.invalid_file')]);
        }
    }

    /**
     * @template T
     *
     * @param  callable(string, string, string): T  $callback
     * @return T
     */
    private function withUpload(TemporaryUploadedFile $file, callable $callback): mixed
    {
        $bytes = $file->get();
        if (! is_string($bytes)) {
            throw ValidationException::withMessages(['dataset' => __('capell-address::import.invalid_file')]);
        }

        $stream = tmpfile();
        if ($stream === false) {
            throw new RuntimeException('Unable to stage country upload.');
        }

        try {
            if (fwrite($stream, $bytes) !== strlen($bytes) || ! fflush($stream)) {
                throw new RuntimeException('Unable to stage country upload.');
            }

            $metadata = stream_get_meta_data($stream);
            $path = $metadata['uri'] ?? null;
            if (! is_string($path) || $path === '') {
                throw new RuntimeException('Unable to stage country upload.');
            }

            return $callback(
                $path,
                strtolower($file->getClientOriginalExtension()),
                hash('sha256', $bytes),
            );
        } finally {
            fclose($stream);
        }
    }
}
