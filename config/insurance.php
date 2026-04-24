<?php

// Hard-coded fallbacks only. The runtime values come from the `insurance_settings`
// database table (admin-editable via /admin/insurance-settings). These fallbacks
// kick in if someone queries before the migration/seed has run.
return [
    // Standard price a student pays for a paid insurance policy, in euro cents (€).
    'default_price_cents' => env('INSURANCE_DEFAULT_PRICE_CENTS', 22000),

    // Standard internal cost of issuing one insurance policy, in euro cents (€).
    'default_cost_cents'  => env('INSURANCE_DEFAULT_COST_CENTS', 7000),

    // Comma-separated list of emails receiving the internal insurance report
    // (consumed by the future digest job; not used in this PR).
    'report_recipients'   => array_filter(explode(',', env('INSURANCE_REPORT_EMAILS', ''))),
];
