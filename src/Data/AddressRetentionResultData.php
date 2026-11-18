<?php

declare(strict_types=1);

namespace Capell\Address\Data;

final readonly class AddressRetentionResultData
{
    public function __construct(
        public int $matchedRecords,
        public int $affectedRecords,
        public int $heldRecords,
    ) {}
}
