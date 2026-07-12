<?php

declare(strict_types=1);

return [
    'storage_tables_label' => 'Address storage tables',
    'storage_tables_passed' => 'The countries and addresses tables are present.',
    'storage_tables_failed' => 'Missing tables: :tables.',
    'storage_tables_remediation' => 'Run the Address package migrations before using address records.',
    'data_quality_label' => 'Address data quality',
    'data_quality_missing_tables' => 'Address data quality could not be checked because storage tables are missing.',
    'data_quality_missing_tables_remediation' => 'Run the Address package migrations, then rerun diagnostics.',
    'data_quality_passed' => 'Checked :addresses address(es); no blocking country or coordinate issues were found.',
    'data_quality_failed' => 'Checked :addresses address(es); :countries missing enabled country and :coordinates invalid coordinate issue(s) were found.',
    'data_quality_remediation' => 'Review Address records with missing countries or invalid latitude/longitude metadata.',
    'duplicate_addresses_label' => 'Duplicate address quality',
    'duplicate_addresses_missing_tables' => 'Duplicate address quality could not be checked because storage tables are missing.',
    'duplicate_addresses_passed' => 'Checked :addresses address(es); no likely duplicate groups were found.',
    'duplicate_addresses_failed' => 'Checked :addresses address(es); :duplicates address(es) appear in :groups likely duplicate group(s).',
    'duplicate_addresses_remediation' => 'Review duplicate address groups before adding merge or cleanup workflows.',
    'providers_label' => 'Address validation and geocoding providers',
    'providers_message' => 'Detected :validation validation provider(s) and :geocoding geocoding provider(s). Providers are optional extension points.',
];
