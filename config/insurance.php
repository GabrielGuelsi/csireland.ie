<?php

return [
    // Standard price a student pays for a paid insurance policy (in cents).
    'default_price_cents' => env('INSURANCE_DEFAULT_PRICE_CENTS', 22000),

    // Standard internal cost of issuing one insurance policy (in cents).
    // Used to compute "how much we're giving away" on bonificado policies.
    'default_cost_cents'  => env('INSURANCE_DEFAULT_COST_CENTS', 22000),

    // Comma-separated list of emails receiving the internal insurance report
    // (consumed by the future digest job; not used in this PR).
    'report_recipients'   => array_filter(explode(',', env('INSURANCE_REPORT_EMAILS', ''))),
];
