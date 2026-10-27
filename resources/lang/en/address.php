<?php

declare(strict_types=1);

return [
    'delete_bulk_modal' => [
        'heading' => 'Delete these addresses?',
        'description' => 'For each selected record: This hides the address from address lists. It can be restored from the deleted records filter; no formatted address copy is created for records that reference it.',
    ],
    'delete_modal' => [
        'heading' => 'Delete this address?',
        'description' => 'This hides the address from address lists. It can be restored from the deleted records filter; no formatted address copy is created for records that reference it.',
    ],
    'force_delete_bulk_modal' => [
        'heading' => 'Permanently delete these addresses?',
        'description' => 'For each selected record: This permanently removes the address. Records that reference it will no longer be able to load it; no formatted address copy is created. This cannot be undone.',
    ],
];
