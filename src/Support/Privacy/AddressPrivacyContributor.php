<?php

declare(strict_types=1);

namespace Capell\Address\Support\Privacy;

use Capell\Address\Actions\BuildAddressPrivacyExportAction;
use Capell\Address\Actions\EraseAddressPrivacyDataAction;
use Capell\Core\Contracts\Privacy\PrivacySubjectEraser;
use Capell\Core\Contracts\Privacy\PrivacySubjectExporter;
use Capell\Core\Data\Privacy\PrivacyErasureReceiptData;
use Capell\Core\Data\Privacy\PrivacyExportSectionData;
use Capell\Core\Data\Privacy\PrivacySubject;
use Capell\Core\Enums\PrivacyErasureMode;
use Illuminate\Database\Eloquent\Model;
use Override;

/**
 * Exports and erases the addresses a subject site owns through Core's privacy registry.
 */
final class AddressPrivacyContributor implements PrivacySubjectEraser, PrivacySubjectExporter
{
    public const string KEY = 'address';

    #[Override]
    public function contributor(): string
    {
        return self::KEY;
    }

    #[Override]
    public function supports(PrivacySubject $subject): bool
    {
        return $subject->model instanceof Model;
    }

    #[Override]
    public function export(PrivacySubject $subject): PrivacyExportSectionData
    {
        return new PrivacyExportSectionData($subject->model instanceof Model ? BuildAddressPrivacyExportAction::run($subject->model) : []);
    }

    #[Override]
    public function erase(PrivacySubject $subject, PrivacyErasureMode $mode): PrivacyErasureReceiptData
    {
        $affectedRecords = $subject->model instanceof Model ? EraseAddressPrivacyDataAction::run($subject->model) : 0;

        return new PrivacyErasureReceiptData(self::KEY, PrivacyErasureMode::Delete, $affectedRecords);
    }

    #[Override]
    public function retainsSuppressionMarker(): bool
    {
        return false;
    }
}
