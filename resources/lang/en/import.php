<?php

declare(strict_types=1);

return [
    'action' => 'Import countries',
    'manual' => 'New country (advanced)',
    'upload' => 'Upload dataset',
    'dataset' => 'Country dataset',
    'format_help' => 'Upload JSON country rows (or a countries array), or CSV with name, iso2 and iso3 columns. Maximum 2 MB.',
    'restore' => 'Restore matching deleted countries',
    'disable_missing' => 'Disable active countries missing from this dataset',
    'review' => 'Review changes',
    'review_help' => 'Nothing has been saved. Skipped rows include unchanged countries and invalid rows. Go back to replace the file or change options, then review again.',
    'apply' => 'Apply import',
    'apply_failed' => 'Import was not applied',
    'conflicting_rows' => 'No changes were saved. Check for duplicate ISO codes or matching deleted countries; enable restoration if appropriate, then review again.',
    'confirm_disable' => 'I confirm that :count active countries will be disabled.',
    'invalid_file' => 'Choose a valid JSON or CSV country dataset and review it again.',
    'review_changed' => 'The dataset, options or countries have changed. Go back and review the import again before applying.',
    'empty_heading' => 'No countries found',
    'empty_description' => 'Import a country dataset to get started. You can review all changes before applying them.',
    'no_active_countries' => 'No active countries are available. Import countries or ask an administrator to enable one, then return to this address.',
    'completed' => 'Country import completed with :changed changed country record(s).',
    'dry_run' => 'Country import dry run found :changed country record(s) that would change.',
    'path_required' => 'A country dataset path is required.',
    'counts' => [
        'created' => 'Created: :count',
        'updated' => 'Updated: :count',
        'restored' => 'Restored: :count',
        'disabled' => 'Disabled: :count',
        'skipped' => 'Skipped: :count',
    ],
];
