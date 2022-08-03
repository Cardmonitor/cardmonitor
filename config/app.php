<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Application Name
    |--------------------------------------------------------------------------
    |
    | This value is the name of your application. This value is used when the
    | framework needs to place the application's name in a notification or
    | any other location as required by the application or its packages.
    |
    */

    'name' => env('APP_NAME', 'Laravel'),

    /*
    |--------------------------------------------------------------------------
    | Application Environment
    |--------------------------------------------------------------------------
    |
    | This value determines the "environment" your application is currently
    | running in. This may determine how you prefer to configure various
    | services the application utilizes. Set this in your ".env" file.
    |
    */

    'env' => env('APP_ENV', 'production'),

    /*
    |--------------------------------------------------------------------------
    | Application Debug Mode
    |--------------------------------------------------------------------------
    |
    | When your application is in debug mode, detailed error messages with
    | stack traces will be shown on every error that occurs within your
    | application. If disabled, a simple generic error page is shown.
    |
    */

    'debug' => env('APP_DEBUG', false),

    /*
    |--------------------------------------------------------------------------
    | Application URL
    |--------------------------------------------------------------------------
    |
    | This URL is used by the console to properly generate URLs when using
    | the Artisan command line tool. You should set this to the root of
    | your application so that it is used when running Artisan tasks.
    |
    */

    'url' => env('APP_URL', 'http://localhost'),

    'asset_url' => env('ASSET_URL', null),

    /*
    |--------------------------------------------------------------------------
    | Application Timezone
    |--------------------------------------------------------------------------
    |
    | Here you may specify the default timezone for your application, which
    | will be used by the PHP date and date-time functions. We have gone
    | ahead and set this to a sensible default for you out of the box.
    |
    */

    'timezone' => 'Europe/Berlin',

    /*
    |--------------------------------------------------------------------------
    | Application Locale Configuration
    |--------------------------------------------------------------------------
    |
    | The application locale determines the default locale that will be used
    | by the translation service provider. You are free to set this value
    | to any of the locales which will be supported by the application.
    |
    */

    'locale' => 'de',

    'available_locales' => [
        'de',
        'en',
    ],

    'iso3166_names' => [
        'AF' => 'Afghanistan',
        'EG' => 'Ägypten',
        'AL' => 'Albanien',
        'DZ' => 'Algerien',
        'AD' => 'Andorra',
        'AO' => 'Angola',
        'AI' => 'Anguilla',
        'AQ' => 'Antarktis',
        'AG' => 'Antigua und Barbuda',
        'GQ' => 'Äquatorial Guinea',
        'AR' => 'Argentinien',
        'AM' => 'Armenien',
        'AW' => 'Aruba',
        'AZ' => 'Aserbaidschan',
        'ET' => 'Äthiopien',
        'AU' => 'Australien',
        'BS' => 'Bahamas',
        'BH' => 'Bahrain',
        'BD' => 'Bangladesh',
        'BB' => 'Barbados',
        'BE' => 'Belgien',
        'BZ' => 'Belize',
        'BJ' => 'Benin',
        'BM' => 'Bermudas',
        'BT' => 'Bhutan',
        'MM' => 'Birma',
        'BO' => 'Bolivien',
        'BA' => 'Bosnien-Herzegowina',
        'BW' => 'Botswana',
        'BV' => 'Bouvet Inseln',
        'BR' => 'Brasilien',
        'IO' => 'Britisch-Indischer Ozean',
        'BN' => 'Brunei',
        'BG' => 'Bulgarien',
        'BF' => 'Burkina Faso',
        'BI' => 'Burundi',
        'CL' => 'Chile',
        'CN' => 'China',
        'CX' => 'Christmas Island',
        'CK' => 'Cook Inseln',
        'CR' => 'Costa Rica',
        'DK' => 'Dänemark',
        'DE' => 'Deutschland',
        'DJ' => 'Djibuti',
        'DM' => 'Dominika',
        'DO' => 'Dominikanische Republik',
        'EC' => 'Ecuador',
        'SV' => 'El Salvador',
        'CI' => 'Elfenbeinküste',
        'ER' => 'Eritrea',
        'EE' => 'Estland',
        'FK' => 'Falkland Inseln',
        'FO' => 'Färöer Inseln',
        'FJ' => 'Fidschi',
        'FI' => 'Finnland',
        'FR' => 'Frankreich',
        'GF' => 'französisch Guyana',
        'PF' => 'Französisch Polynesien',
        'TF' => 'Französisches Süd-Territorium',
        'GA' => 'Gabun',
        'GM' => 'Gambia',
        'GE' => 'Georgien',
        'GH' => 'Ghana',
        'GI' => 'Gibraltar',
        'GD' => 'Grenada',
        'GR' => 'Griechenland',
        'GL' => 'Grönland',
        'UK' => 'Großbritannien',
        'GB' => 'Großbritannien (UK)',
        'GP' => 'Guadeloupe',
        'GU' => 'Guam',
        'GT' => 'Guatemala',
        'GN' => 'Guinea',
        'GW' => 'Guinea Bissau',
        'GY' => 'Guyana',
        'HT' => 'Haiti',
        'HM' => 'Heard und McDonald Islands',
        'HN' => 'Honduras',
        'HK' => 'Hong Kong',
        'IN' => 'Indien',
        'ID' => 'Indonesien',
        'IQ' => 'Irak',
        'IR' => 'Iran',
        'IE' => 'Irland',
        'IS' => 'Island',
        'IL' => 'Israel',
        'IT' => 'Italien',
        'JM' => 'Jamaika',
        'JP' => 'Japan',
        'YE' => 'Jemen',
        'JO' => 'Jordanien',
        'YU' => 'Jugoslawien',
        'KY' => 'Kaiman Inseln',
        'KH' => 'Kambodscha',
        'CM' => 'Kamerun',
        'CA' => 'Kanada',
        'CV' => 'Kap Verde',
        'KZ' => 'Kasachstan',
        'KE' => 'Kenia',
        'KG' => 'Kirgisistan',
        'KI' => 'Kiribati',
        'CC' => 'Kokosinseln',
        'CO' => 'Kolumbien',
        'KM' => 'Komoren',
        'CG' => 'Kongo',
        'CD' => 'Demokratische Republik Kongo',
        'HR' => 'Kroatien',
        'CU' => 'Kuba',
        'KW' => 'Kuwait',
        'LA' => 'Laos',
        'LS' => 'Lesotho',
        'LV' => 'Lettland',
        'LB' => 'Libanon',
        'LR' => 'Liberia',
        'LY' => 'Libyen',
        'LI' => 'Liechtenstein',
        'LT' => 'Litauen',
        'LU' => 'Luxemburg',
        'MO' => 'Macao',
        'MG' => 'Madagaskar',
        'MW' => 'Malawi',
        'MY' => 'Malaysia',
        'MV' => 'Malediven',
        'ML' => 'Mali',
        'MT' => 'Malta',
        'MP' => 'Marianen',
        'MA' => 'Marokko',
        'MH' => 'Marshall Inseln',
        'MQ' => 'Martinique',
        'MR' => 'Mauretanien',
        'MU' => 'Mauritius',
        'YT' => 'Mayotte',
        'MK' => 'Mazedonien',
        'MX' => 'Mexiko',
        'FM' => 'Mikronesien',
        'MZ' => 'Mocambique',
        'MD' => 'Moldavien',
        'MC' => 'Monaco',
        'MN' => 'Mongolei',
        'MS' => 'Montserrat',
        'NA' => 'Namibia',
        'NR' => 'Nauru',
        'NP' => 'Nepal',
        'NC' => 'Neukaledonien',
        'NZ' => 'Neuseeland',
        'NI' => 'Nicaragua',
        'NL' => 'Niederlande',
        'AN' => 'Niederländische Antillen',
        'NE' => 'Niger',
        'NG' => 'Nigeria',
        'NU' => 'Niue',
        'KP' => 'Nord Korea',
        'NF' => 'Norfolk Inseln',
        'NO' => 'Norwegen',
        'OM' => 'Oman',
        'AT' => 'Österreich',
        'PK' => 'Pakistan',
        'PS' => 'Palästina',
        'PW' => 'Palau',
        'PA' => 'Panama',
        'PG' => 'Papua Neuguinea',
        'PY' => 'Paraguay',
        'PE' => 'Peru',
        'PH' => 'Philippinen',
        'PN' => 'Pitcairn',
        'PL' => 'Polen',
        'PT' => 'Portugal',
        'PR' => 'Puerto Rico',
        'QA' => 'Qatar',
        'RE' => 'Reunion',
        'RW' => 'Ruanda',
        'RO' => 'Rumänien',
        'RU' => 'Russland',
        'LC' => 'Saint Lucia',
        'ZM' => 'Sambia',
        'AS' => 'Samoa',
        'WS' => 'Samoa',
        'SM' => 'San Marino',
        'ST' => 'Sao Tome',
        'SA' => 'Saudi Arabien',
        'SE' => 'Schweden',
        'CH' => 'Schweiz',
        'SN' => 'Senegal',
        'SC' => 'Seychellen',
        'SL' => 'Sierra Leone',
        'SG' => 'Singapur',
        'SK' => 'Slowakei',
        'SI' => 'Slowenien',
        'SB' => 'Solomon Inseln',
        'SO' => 'Somalia',
        'GS' => 'Südgeorgien und die Südlichen Sandwichinseln',
        'ES' => 'Spanien',
        'LK' => 'Sri Lanka',
        'SH' => 'St. Helena',
        'KN' => 'St. Kitts Nevis Anguilla',
        'PM' => 'St. Pierre und Miquelon',
        'VC' => 'St. Vincent',
        'KR' => 'Süd Korea',
        'ZA' => 'Südafrika',
        'SD' => 'Sudan',
        'SR' => 'Surinam',
        'SJ' => 'Svalbard und Jan Mayen Islands',
        'SZ' => 'Swasiland',
        'SY' => 'Syrien',
        'TJ' => 'Tadschikistan',
        'TW' => 'Taiwan',
        'TZ' => 'Tansania',
        'TH' => 'Thailand',
        'TP' => 'Timor',
        'TG' => 'Togo',
        'TK' => 'Tokelau',
        'TO' => 'Tonga',
        'TT' => 'Trinidad Tobago',
        'TD' => 'Tschad',
        'CZ' => 'Tschechische Republik',
        'TN' => 'Tunesien',
        'TR' => 'Türkei',
        'TM' => 'Turkmenistan',
        'TC' => 'Turks und Kaikos Inseln',
        'TV' => 'Tuvalu',
        'UG' => 'Uganda',
        'UA' => 'Ukraine',
        'HU' => 'Ungarn',
        'UY' => 'Uruguay',
        'UZ' => 'Usbekistan',
        'VU' => 'Vanuatu',
        'VA' => 'Vatikan',
        'VE' => 'Venezuela',
        'AE' => 'Vereinigte Arabische Emirate',
        'US' => 'Vereinigte Staaten von Amerika',
        'VN' => 'Vietnam',
        'VG' => 'Virgin Island (Brit.)',
        'VI' => 'Virgin Island (USA)',
        'WF' => 'Wallis et Futuna',
        'BY' => 'Weissrussland',
        'EH' => 'Westsahara',
        'CF' => 'Zentralafrikanische Republik',
        'ZW' => 'Zimbabwe',
        'CY' => 'Zypern',
    ],

    /*
    |--------------------------------------------------------------------------
    | Application Fallback Locale
    |--------------------------------------------------------------------------
    |
    | The fallback locale determines the locale to use when the current one
    | is not available. You may change the value to correspond to any of
    | the language folders that are provided through your application.
    |
    */

    'fallback_locale' => 'en',

    /*
    |--------------------------------------------------------------------------
    | Application Mail
    |--------------------------------------------------------------------------
    |
    | E-Mail to send Info Mails to
    |
    */

    'mail' => env('APP_MAIL'),

    /*
    |--------------------------------------------------------------------------
    | Faker Locale
    |--------------------------------------------------------------------------
    |
    | This locale will be used by the Faker PHP library when generating fake
    | data for your database seeds. For example, this will be used to get
    | localized telephone numbers, street address information and more.
    |
    */

    'faker_locale' => 'de_DE',

    /*
    |--------------------------------------------------------------------------
    | Encryption Key
    |--------------------------------------------------------------------------
    |
    | This key is used by the Illuminate encrypter service and should be set
    | to a random, 32 character string, otherwise these encrypted strings
    | will not be safe. Please do this before deploying an application!
    |
    */

    'key' => env('APP_KEY'),

    'cipher' => 'AES-256-CBC',

    /*
    |--------------------------------------------------------------------------
    | Autoloaded Service Providers
    |--------------------------------------------------------------------------
    |
    | The service providers listed here will be automatically loaded on the
    | request to your application. Feel free to add your own services to
    | this array to grant expanded functionality to your applications.
    |
    */

    'providers' => [

        /*
         * Laravel Framework Service Providers...
         */
        Illuminate\Auth\AuthServiceProvider::class,
        Illuminate\Broadcasting\BroadcastServiceProvider::class,
        Illuminate\Bus\BusServiceProvider::class,
        Illuminate\Cache\CacheServiceProvider::class,
        Illuminate\Foundation\Providers\ConsoleSupportServiceProvider::class,
        Illuminate\Cookie\CookieServiceProvider::class,
        Illuminate\Database\DatabaseServiceProvider::class,
        Illuminate\Encryption\EncryptionServiceProvider::class,
        Illuminate\Filesystem\FilesystemServiceProvider::class,
        Illuminate\Foundation\Providers\FoundationServiceProvider::class,
        Illuminate\Hashing\HashServiceProvider::class,
        Illuminate\Mail\MailServiceProvider::class,
        Illuminate\Notifications\NotificationServiceProvider::class,
        Illuminate\Pagination\PaginationServiceProvider::class,
        Illuminate\Pipeline\PipelineServiceProvider::class,
        Illuminate\Queue\QueueServiceProvider::class,
        Illuminate\Redis\RedisServiceProvider::class,
        Illuminate\Auth\Passwords\PasswordResetServiceProvider::class,
        Illuminate\Session\SessionServiceProvider::class,
        Illuminate\Translation\TranslationServiceProvider::class,
        Illuminate\Validation\ValidationServiceProvider::class,
        Illuminate\View\ViewServiceProvider::class,

        /*
         * Package Service Providers...
         */

        /*
         * Application Service Providers...
         */
        App\Providers\AppServiceProvider::class,
        App\Providers\AuthServiceProvider::class,
        // App\Providers\BroadcastServiceProvider::class,
        App\Providers\EventServiceProvider::class,
        App\Providers\TelescopeServiceProvider::class,
        App\Providers\RouteServiceProvider::class,

    ],

    /*
    |--------------------------------------------------------------------------
    | Class Aliases
    |--------------------------------------------------------------------------
    |
    | This array of class aliases will be registered when this application
    | is started. However, feel free to register as many as you wish as
    | the aliases are "lazy" loaded so they don't hinder performance.
    |
    */

    'aliases' => [

        'App' => Illuminate\Support\Facades\App::class,
        'Arr' => Illuminate\Support\Arr::class,
        'Artisan' => Illuminate\Support\Facades\Artisan::class,
        'Auth' => Illuminate\Support\Facades\Auth::class,
        'Blade' => Illuminate\Support\Facades\Blade::class,
        'Broadcast' => Illuminate\Support\Facades\Broadcast::class,
        'Bus' => Illuminate\Support\Facades\Bus::class,
        'Cache' => Illuminate\Support\Facades\Cache::class,
        'Config' => Illuminate\Support\Facades\Config::class,
        'Cookie' => Illuminate\Support\Facades\Cookie::class,
        'Crypt' => Illuminate\Support\Facades\Crypt::class,
        'DB' => Illuminate\Support\Facades\DB::class,
        'Eloquent' => Illuminate\Database\Eloquent\Model::class,
        'Event' => Illuminate\Support\Facades\Event::class,
        'File' => Illuminate\Support\Facades\File::class,
        'Gate' => Illuminate\Support\Facades\Gate::class,
        'Hash' => Illuminate\Support\Facades\Hash::class,
        'Lang' => Illuminate\Support\Facades\Lang::class,
        'Log' => Illuminate\Support\Facades\Log::class,
        'Mail' => Illuminate\Support\Facades\Mail::class,
        'Notification' => Illuminate\Support\Facades\Notification::class,
        'Password' => Illuminate\Support\Facades\Password::class,
        'Queue' => Illuminate\Support\Facades\Queue::class,
        'Redirect' => Illuminate\Support\Facades\Redirect::class,
        'Redis' => Illuminate\Support\Facades\Redis::class,
        'Request' => Illuminate\Support\Facades\Request::class,
        'Response' => Illuminate\Support\Facades\Response::class,
        'Route' => Illuminate\Support\Facades\Route::class,
        'Schema' => Illuminate\Support\Facades\Schema::class,
        'Session' => Illuminate\Support\Facades\Session::class,
        'Storage' => Illuminate\Support\Facades\Storage::class,
        'Str' => Illuminate\Support\Str::class,
        'URL' => Illuminate\Support\Facades\URL::class,
        'Validator' => Illuminate\Support\Facades\Validator::class,
        'View' => Illuminate\Support\Facades\View::class,

    ],

    'fints' => [
        'registration_no' => env('FHP_REGISTRATION_NO'),
        'bank_url' => env('FHP_BANK_URL'),
        'bank_code' => env('FHP_BANK_CODE'),
        'username' => env('FHP_ONLINE_BANKING_USERNAME'),
        'pin' => env('FHP_ONLINE_BANKING_PIN'),
        'tan_mechanism' => env('FHP_TAN_MECHANISM'),
    ],

];
