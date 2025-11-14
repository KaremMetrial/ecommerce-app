<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Default Language
    |--------------------------------------------------------------------------
    |
    | This option determines the default locale for the application.
    | This value is used when the current locale is not available.
    |
    */
    'default' => env('APP_LOCALE', 'en'),

    /*
    |--------------------------------------------------------------------------
    | Supported Languages
    |--------------------------------------------------------------------------
    |
    | These are the languages that are supported by the application.
    | The key should be the language code and the value should be the language name.
    |
    */
    'supported' => [
        'en' => 'English',
        'es' => 'Español',
        'fr' => 'Français',
        'de' => 'Deutsch',
        'it' => 'Italiano',
        'pt' => 'Português',
        'ru' => 'Русский',
        'zh' => '中文',
        'ja' => '日本語',
        'ar' => 'العربية',
    ],

    /*
    |--------------------------------------------------------------------------
    | Fallback Language
    |--------------------------------------------------------------------------
    |
    | This option determines the fallback locale for the application.
    | This value is used when the current locale is not available.
    |
    */
    'fallback' => env('APP_FALLBACK_LOCALE', 'en'),

    /*
    |--------------------------------------------------------------------------
    | Auto Detect Language
    |--------------------------------------------------------------------------
    |
    | This option determines if the application should auto-detect the user's language
    | from their browser preferences or other headers.
    |
    */
    'auto_detect' => env('AUTO_DETECT_LANGUAGE', true),

    /*
    |--------------------------------------------------------------------------
    | Language Switching
    |--------------------------------------------------------------------------
    |
    | These options control how language switching works in the application.
    |
    */
    'switching' => [
        'session_key' => 'locale',
        'cookie_name' => 'locale',
        'cookie_expires' => 60 * 24 * 365, // 1 year
    ],

    /*
    |--------------------------------------------------------------------------
    | RTL Languages
    |--------------------------------------------------------------------------
    |
    | These are the languages that are written right-to-left.
    |
    */
    'rtl' => ['ar', 'he', 'fa', 'ur'],

    /*
    |--------------------------------------------------------------------------
    | Currency Mapping
    |--------------------------------------------------------------------------
    |
    | Map languages to their default currencies.
    |
    */
    'currency_mapping' => [
        'en' => 'USD',
        'es' => 'EUR',
        'fr' => 'EUR',
        'de' => 'EUR',
        'it' => 'EUR',
        'pt' => 'BRL',
        'ru' => 'RUB',
        'zh' => 'CNY',
        'ja' => 'JPY',
        'ar' => 'SAR',
    ],
];
