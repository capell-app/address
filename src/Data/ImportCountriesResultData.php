<?php

declare(strict_types=1);

namespace Capell\Address\Data;

final readonly class ImportCountriesResultData
{
    public function __construct(
        public int $created,
        public int $updated,
        public int $skipped,
        public int $disabled,
        public int $restored,
        public bool $dryRun,
    ) {}

    public function changed(): int
    {
        return $this->created + $this->updated + $this->disabled + $this->restored;
    }
}
