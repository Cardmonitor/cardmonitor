<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, SparkPost and others. This file provides a sane default
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'sparkpost' => [
        'secret' => env('SPARKPOST_SECRET'),
    ],

    'cardmarket' => [
        'app_token' => env('CARDMARKET_APP_TOKEN'),
        'app_secret' => env('CARDMARKET_APP_SECRET'),
        'access_token' => env('CARDMARKET_ACCESS_TOKEN'),
        'access_token_secret' => env('CARDMARKET_ACCESS_TOKEN_SECRET'),
    ],

    'dropbox' => [
        'accesstoken' => env('DROPBOX_ACCESSTOKEN'),
        'client_id' => env('DROPBOX_CLIENT_ID'),
        'client_secret' => env('DROPBOX_CLIENT_SECRET'),
        'redirect' => '/login/dropbox/callback',
    ],

    'mqtt' => [
        'host' => env('MQTT_HOST'),
        'port' => env('MQTT_PORT'),
        'client_id' => env('MQTT_CLIENT_ID'),
        'username' => env('MQTT_USERNAME'),
        'password' => env('MQTT_PASSWORD'),
        'cafile' => env('MQTT_CAFILE'),
    ],

    'woocommerce' => [
        'url' => env('WOO_COMMERCE_URL'),
        'consumer_key' => env('WOO_COMMERCE_CONSUMER_KEY'),
        'consumer_secret' => env('WOO_COMMERCE_CONSUMER_SECRET'),
    ],

];
