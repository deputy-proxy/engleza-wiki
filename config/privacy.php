<?php

return [
    // Keep public policy pages visibly marked as drafts until qualified Romanian/EU counsel approves them.
    'policy_status' => env('PRIVACY_POLICY_STATUS', 'draft') === 'published' ? 'published' : 'draft',
    'policy_version' => env('PRIVACY_POLICY_VERSION', '0.1-draft'),
    'cookie_policy_version' => env('COOKIE_POLICY_VERSION', '0.1-draft'),
    'contact_email' => env('PRIVACY_CONTACT_EMAIL'),
];
