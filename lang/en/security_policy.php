<?php

return [
    'title'    => 'Password & Account Security Policy',
    'subtitle' => 'Rules enforced by the application for every account, kept here as a single page for audit evidence.',
    'footer'   => "This page reflects the rules enforced in the application's authentication code. It is updated by hand whenever those rules change.",

    'group' => [
        'password' => 'Password rules',
        'login'    => 'Sign-in protection',
        'account'  => 'Account & audit',
    ],

    'item' => [
        'min_length' => [
            'title' => 'Minimum password length',
            'desc'  => 'Every account password must be at least 8 characters long.',
        ],
        'confirmation' => [
            'title' => 'Password confirmation required',
            'desc'  => 'A new password must be entered twice and match, when registering, changing, or resetting a password.',
        ],
        'hashing' => [
            'title' => 'Passwords are hashed, never stored in plain text',
            'desc'  => 'Passwords are hashed with bcrypt before being saved; the original value is never stored or logged.',
        ],
        'reset_delivery' => [
            'title' => 'Password resets use a signed, single-use link',
            'desc'  => "A password reset is only possible via a time-limited link emailed to the account holder's verified address; the link cannot be reused.",
        ],
        'login_throttle' => [
            'title' => 'Sign-in attempts are throttled',
            'desc'  => 'After 5 failed sign-in attempts for the same account and network address, further attempts are blocked with an increasing delay.',
        ],
        'registration_throttle' => [
            'title' => 'Registration is rate-limited',
            'desc'  => 'New account registration is limited to 3 attempts per minute from the same network address.',
        ],
        'reset_throttle' => [
            'title' => 'Password reset requests are rate-limited',
            'desc'  => 'Requests for a password reset link are limited to 6 per minute.',
        ],
        'deactivation' => [
            'title' => 'Deactivated accounts are signed out immediately',
            'desc'  => 'A deactivated account is signed out on its next request, and every active session for that account is revoked immediately.',
        ],
        'auditing' => [
            'title' => 'Every security event is recorded',
            'desc'  => "Sign-ins, sign-outs, failed attempts, lockouts, and password changes are all written to the system's append-only audit log.",
        ],
    ],
];
