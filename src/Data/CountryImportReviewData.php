<?php

declare(strict_types=1);

namespace Capell\Address\Data;

final readonly class CountryImportReviewData
{
    public function __construct(
        public ImportCountriesResultData $counts,
        public string $fingerprint,
    ) {}
}
