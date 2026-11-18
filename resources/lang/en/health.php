<?php

declare(strict_types=1);

return [
    'storage_tables_label' => 'Address: database tables present',
    'storage_tables_passed' => 'The countries and addresses tables are present.',
    'storage_tables_failed' => 'Missing tables: :tables.',
    'storage_tables_remediation' => 'Run the Address package migrations before using address records.',
    'data_quality_label' => 'Address: address details complete',
    'data_quality_missing_tables' => 'Address data quality could not be checked because storage tables are missing.',
    'data_quality_missing_tables_remediation' => 'Run the Address package migrations, then rerun diagnostics.',
    'data_quality_passed' => 'Addresses checked: :addresses. No blocking country or coordinate issues were found.',
    'data_quality_failed' => 'Addresses checked: :addresses. Addresses missing an enabled country: :countries. Invalid coordinate issues: :coordinates.',
    'data_quality_remediation' => 'Review Address records with missing countries or invalid latitude/longitude metadata.',
    'duplicate_addresses_label' => 'Address: duplicate addresses checked',
    'duplicate_addresses_missing_tables' => 'Duplicate address quality could not be checked because storage tables are missing.',
    'duplicate_addresses_passed' => 'Addresses checked: :addresses. No likely duplicate groups were found.',
    'duplicate_addresses_failed' => 'Addresses checked: :addresses. Addresses in likely duplicate groups: :duplicates, across :groups groups.',
    'duplicate_addresses_remediation' => 'Review duplicate address groups before adding merge or cleanup workflows.',
    'providers_label' => 'Address: optional address services detected',
    'providers_message' => 'Detected validation services: :validation. Detected coordinate services: :geocoding. Configure an address service only if your site needs it.',
];
