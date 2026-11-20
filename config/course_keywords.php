<?php

/*
|--------------------------------------------------------------------------
| Jobs related to your course (Open Positions page, students and alumni)
|--------------------------------------------------------------------------
|
| This is the one place to edit. It is a plain word list, not AI: when a student or
| alumnus opens the job list, jobs whose title, requirements or description contain
| these words (plus the words of their own course and CV skills) are listed first,
| best match first, then newest. Every other job still follows, newest first.
| Nothing is ever hidden.
|
| Each course has:
|   aliases   how the course can be written on a profile. A profile matches when its
|             course text contains an alias as whole words, in any letter case:
|             "BSIT", "BS Information Technology" and "Bachelor of Science in
|             Information Technology" all match the "information technology" entry.
|   keywords  one word each, in any letter case. A SHORT ALL-CAPITAL word (2 or 3
|             letters, like IT or HR) only matches when it is written in capitals in
|             the job, so the keyword IT matches "IT Support" but not "it is".
|             Plural "s" is ignored ("developers" matches "developer").
|
| Add a course by adding a block. A course that is not listed here still works:
| the words of its own name (for example "Psychology") are used on their own.
|
*/

return [

    // How many of the newest open jobs (after the search and filters) are scored for one page view.
    // Jobs beyond this are not hidden; they simply follow in the normal newest-first order.
    'pool' => 300,

    // A job needs this score to count as related. Title = 3, requirements = 2, description = 1,
    // counted once per keyword, so one passing mention in a long description is not enough.
    'min_score' => 2,

    'courses' => [

        'information-technology' => [
            'aliases' => ['bsit', 'bs it', 'information technology', 'bscs', 'computer science', 'bsis', 'information systems', 'bsemc', 'entertainment and multimedia computing'],
            'keywords' => [
                'IT', 'ICT', 'software', 'web', 'website', 'network', 'networking', 'developer', 'programmer',
                'programming', 'technology', 'technical', 'technician', 'computer', 'database', 'system', 'helpdesk',
                'cybersecurity', 'tech', 'frontend', 'backend', 'mobile', 'multimedia', 'graphic',
            ],
        ],

        'hospitality' => [
            'aliases' => ['bshm', 'bshrm', 'hospitality management', 'hotel and restaurant management', 'bstm', 'tourism management'],
            'keywords' => [
                'hotel', 'resort', 'restaurant', 'hospitality', 'tourism', 'tourist', 'food', 'beverage', 'kitchen',
                'chef', 'cook', 'housekeeping', 'barista', 'waiter', 'waitress', 'banquet', 'catering', 'cafe',
                'bartender', 'reservation', 'guest',
            ],
        ],

        'business' => [
            'aliases' => ['bsba', 'business administration', 'bsentrep', 'entrepreneurship', 'bsmm', 'marketing management'],
            'keywords' => [
                'business', 'marketing', 'sales', 'management', 'operations', 'administrative', 'admin', 'office',
                'clerk', 'secretary', 'HR', 'payroll', 'retail', 'customer', 'supervisor', 'merchandiser',
            ],
        ],

        'accountancy' => [
            'aliases' => ['bsa', 'accountancy', 'accounting', 'bsais', 'accounting information system', 'bsma', 'management accounting'],
            'keywords' => [
                'accountant', 'accounting', 'bookkeeper', 'bookkeeping', 'audit', 'auditor', 'tax', 'payroll',
                'finance', 'financial', 'cashier', 'billing', 'ledger',
            ],
        ],

        'education' => [
            'aliases' => ['bsed', 'beed', 'bped', 'secondary education', 'elementary education', 'education', 'teacher education'],
            'keywords' => [
                'teacher', 'teaching', 'tutor', 'school', 'instructor', 'curriculum', 'classroom', 'education',
                'learner', 'faculty', 'trainer',
            ],
        ],

        'criminology' => [
            'aliases' => ['bscrim', 'criminology', 'bs criminology'],
            'keywords' => [
                'security', 'guard', 'police', 'investigator', 'investigation', 'safety', 'law', 'enforcement',
                'patrol', 'compliance',
            ],
        ],

    ],
];
