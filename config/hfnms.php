<?php

return [
    /*
    |--------------------------------------------------------------------------
    | OSRM Road Routing
    |--------------------------------------------------------------------------
    |
    | Public OSRM demo server for snapping cable paths to roads on GIS map.
    | For production, host your own OSRM instance with local OSM data.
    |
    */
    'osrm_url' => env('OSRM_URL', 'https://router.project-osrm.org'),

    'osrm_profile' => env('OSRM_PROFILE', 'driving'),

    /*
    |--------------------------------------------------------------------------
    | Public Self-Registration
    |--------------------------------------------------------------------------
    |
    | When disabled (default), the /register routes return 404. Accounts must
    | be created by an administrator via the user management module. Enable
    | only for environments that intentionally allow open sign-up.
    |
    */
    'allow_registration' => env('ALLOW_REGISTRATION', false),
];
