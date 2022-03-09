<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may configure your settings for cross-origin resource sharing
    | or "CORS". This determines what cross-origin operations may execute
    | in web browsers. You are free to adjust these settings as needed.
    |
    | To learn more: https://developer.mozilla.org/en-US/docs/Web/HTTP/CORS
    |
    */

    'paths' => ['api/*'],

    'allowed_methods' => ['POST', 'GET', 'OPTIONS', 'PUT', 'DELETE'],

    'allowed_origins' => [
        env('APP_URL', 'http://localhost'),
        env('GRAFANA_URL')
    ],

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['Content-Type', 'Cache-Control', 'Authorization', 'X-Requested-With'],

    'exposed_headers' => [],

    'max_age' => 86400,

    'supports_credentials' => true,

];
