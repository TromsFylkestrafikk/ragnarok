<?php

return [
    /*
    | ------------------------------------------------------------------------
    | Entur API parameters and credentials.
    | ------------------------------------------------------------------------
    |
    | Stop places are imported daily and all existing stops are flushed on first
    | imported URL.
    */
    'api' => [
        'auth_url' => env('ENTUR_AUTH_URL'),
        'client_id' => env('ENTUR_CLIENT_ID'),
        'client_secret' => env('ENTUR_SECRET'),
        'audience' => env('ENTUR_AUDIENCE'),
        'api_path' => trim(env('ENTUR_API_PATH'), '/'),
        'env' => env('ENTUR_ENV', 'dev'),
    ],

    'routedata' => [
        'main_xml' => env('ENTUR_ROUTEDATA_XML_MAIN', '_Shared_data.xml'),
        'activation_period' => env('ENTUR_ROUTEDATA_PERIOD', 'P30D'),
    ],

    /*
    | ------------------------------------------------------------------------
    | Array of stop archive URLs to import daily
    | ------------------------------------------------------------------------
    |
    | Stop places are imported daily and all existing stops are flushed on first
    | imported URL.
    */
    'import_stop_archives' => explode(' ', env('ENTUR_STOPS_URLS')),
];
