<?php

declare(strict_types=1);

namespace Capell\Address\Data;

use Spatie\LaravelData\Data;

final class AddressValidationResultData extends Data
{
    /**
     * @param  list<string>  $messages
     * @param  array<string, mixed>  $normalized
     */
    public function __construct(
        public readonly string $provider,
        public readonly bool $valid,
        public readonly ?float $confidence = null,
        public readonly array $messages = [],
        public readonly array $normalized = [],
    ) {}
}
