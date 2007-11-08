<?php

declare(strict_types=1);

use Capell\Address\Actions\SeedAddressScreenshotFixtureAction;
use Capell\Core\Models\Language;

function withAddressScreenshotFixtureEnvironment(Closure $callback): void
{
    putenv('CAPELL_SCREENSHOT_FIXTURE=record-state');
    putenv('CAPELL_SCREENSHOT_APP_PATH=' . base_path());

    try {
        $callback();
    } finally {
        putenv('CAPELL_SCREENSHOT_FIXTURE');
        putenv('CAPELL_SCREENSHOT_APP_PATH');
    }
}

it('seeds populated countries and addresses idempotently', function (): void {
    Language::factory()->english()->create();

    withAddressScreenshotFixtureEnvironment(function (): void {
        $first = SeedAddressScreenshotFixtureAction::run();
        $second = SeedAddressScreenshotFixtureAction::run();

        expect($first)->toBe(['countries' => 4, 'addresses' => 3])
            ->and($second)->toBe($first);
    });
});

it('refuses to seed outside the disposable screenshot environment', function (): void {
    expect(fn (): array => SeedAddressScreenshotFixtureAction::run())
        ->toThrow(RuntimeException::class, 'explicit disposable local screenshot environment');
});

it('requires --force on the address screenshot fixture command', function (): void {
    $this->artisan('capell:address-screenshot-fixture')->assertFailed();
});
