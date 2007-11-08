<?php

declare(strict_types=1);

use Capell\Core\Models\Site;
use Capell\Tests\Support\Concerns\CreatesAdminUser;
use Capell\Tests\Support\ScreenshotManifest;

uses(CreatesAdminUser::class);

beforeEach(function (): void {
    $this->actingAsAdmin();
    putenv('CAPELL_SCREENSHOT_FIXTURE=record-state');
    putenv('CAPELL_SCREENSHOT_APP_PATH=' . base_path());
});

afterEach(function (): void {
    putenv('CAPELL_SCREENSHOT_FIXTURE');
    putenv('CAPELL_SCREENSHOT_APP_PATH');
});

it('opens site settings with a populated address selector', function (): void {
    $site = Site::factory()->withTranslations()->create();
    require __DIR__ . '/../../workbench/routes/screenshot-fixtures.php';
    $response = $this->get(addressSuppressedCaptureUrl('site-settings-fields-where-address-data-is-injected'));
    $response->assertRedirect('/admin/sites/' . $site->getRouteKey() . '/edit');
    expect($site->fresh()->meta['address_id'])->toBeInt();
    $location = $response->baseResponse->headers->get('Location');
    throw_unless(is_string($location), RuntimeException::class, 'The address screenshot response did not redirect to a location.');
    $this->get($location)->assertOk()->assertSee('address_id')->assertSee('Clerkenwell');
});

function addressSuppressedCaptureUrl(string $key): string
{
    return ScreenshotManifest::captureUrl(__DIR__ . '/../../docs/screenshots.json', $key);
}
