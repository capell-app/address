<?php

declare(strict_types=1);

use Capell\Address\Models\Address;
use Illuminate\Console\Command;

use function Pest\Laravel\artisan;

describe('capell:address-faker command', function (): void {
    it('requires a positive count', function (): void {
        artisan('capell:address-faker', [
            '--count' => 0,
        ])
            ->expectsOutput('The --count option must be at least 1.')
            ->assertExitCode(Command::FAILURE);

        expect(Address::query()->count())->toBe(0);
    });

    it('seeds the requested number of addresses', function (): void {
        artisan('capell:address-faker', [
            '--count' => 3,
        ])
            ->expectsOutput('Seeded 3 fake addresses.')
            ->assertExitCode(Command::SUCCESS);

        expect(Address::query()->count())->toBe(3);
    });
});
