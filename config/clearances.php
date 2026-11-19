<?php

/*
| Required clearances (Phase 14): the NBI clearance and the barangay clearance.
| One place to change the rules, so the form, the server checks and the messages agree.
*/

return [

    // Which disk holds the PDFs. Empty means the app's default disk: the PRIVATE Supabase
    // bucket on Vercel (FILESYSTEM_DISK=s3) and storage/app/private on a local machine.
    // It must never be the "public" disk, and the code refuses to run if it is.
    'disk' => env('CLEARANCE_DISK'),

    // Folder inside that disk. Files are saved as clearances/{profile id}/{type}-{random}.pdf.
    'folder' => 'clearances',

    // Largest file, in kilobytes (4096 = 4 MB). Vercel rejects whole requests above about
    // 4.5 MB, so keep this at 4 MB or below and upload one file per request.
    'max_kb' => 4096,

    // A validity date more than this many years ahead is treated as a typing mistake.
    'max_validity_years' => 2,

    // The documents. Add one here and it appears everywhere (page, apply check, company view).
    'types' => [
        'nbi' => [
            'label' => 'NBI clearance',
            'sentence' => 'NBI clearance',
            'download' => 'NBI-Clearance.pdf',
        ],
        'barangay' => [
            'label' => 'Barangay clearance',
            'sentence' => 'barangay clearance',
            'download' => 'Barangay-Clearance.pdf',
        ],
    ],

];
