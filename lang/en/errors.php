<?php

return [
    'system_name' => 'Internal Travel Permit System',
    'code'        => 'Error :code',
    'go_back'     => 'Go back',
    'go_home'     => 'Go to home page',
    'try_again'   => 'Try again',

    '401' => [
        'title'   => 'Please sign in',
        'message' => 'You need to sign in to your NIMR account to view this page.',
    ],
    '403' => [
        'title'   => 'Access denied',
        'message' => 'You do not have permission to view this page or perform this action. If you believe this is a mistake, please contact a System Administrator.',
    ],
    '404' => [
        'title'   => 'Page not found',
        'message' => 'The page you are looking for does not exist or may have been moved. Please check the address, or return to the home page.',
    ],
    '413' => [
        'title'   => 'File too large',
        'message' => 'The file you tried to upload is larger than the system allows. Please choose a smaller file and try again.',
    ],
    '419' => [
        'title'   => 'Your session has expired',
        'message' => 'For your security, this page expired after a period of inactivity. Please go back, refresh the page and try again.',
    ],
    '429' => [
        'title'   => 'Too many attempts',
        'message' => 'You have made too many requests in a short time. Please wait a moment before trying again.',
        'wait'    => 'You can try again in :seconds second.|You can try again in :seconds seconds.',
    ],
    '500' => [
        'title'   => 'Something went wrong',
        'message' => 'An unexpected error occurred on our side. The technical team has been notified automatically. Please try again in a few minutes.',
    ],
    '503' => [
        'title'   => 'Temporarily unavailable',
        'message' => 'The system is undergoing maintenance and will be back shortly. Please try again in a few minutes.',
    ],
    '4xx' => [
        'title'   => 'Request could not be completed',
        'message' => 'Something about this request was not right. Please go back and try again.',
    ],
];
