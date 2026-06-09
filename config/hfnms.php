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
];
