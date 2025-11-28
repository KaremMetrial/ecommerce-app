<?php
use Illuminate\Support\Str;

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
        'scheme' => 'https',
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Meta (Facebook) Services
    |--------------------------------------------------------------------------
    |
    | Configuration for Meta/Facebook services including Pixel and Graph API
    |
    */

    'meta' => [
        'pixel_id' => env('META_PIXEL_ID'),
        'access_token' => env('META_ACCESS_TOKEN'),
        'test_event_code' => env('META_TEST_EVENT_CODE'),
        'enabled' => env('META_ENABLED', false),
        'app_id' => env('META_APP_ID'),
        'app_secret' => env('META_APP_SECRET'),
        'page_id' => env('META_PAGE_ID'),
        'instagram_account_id' => env('META_INSTAGRAM_ACCOUNT_ID'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Google Services
    |--------------------------------------------------------------------------
    |
    | Configuration for Google services including Analytics, Ads, and Maps
    |
    */

    'google' => [
        'analytics' => [
            'measurement_id' => env('GA_MEASUREMENT_ID'),
            'api_secret' => env('GA_API_SECRET'),
            'enabled' => env('GA_ENABLED', false),
        ],
        'ads' => [
            'conversion_id' => env('GOOGLE_ADS_CONVERSION_ID'),
            'conversion_label' => env('GOOGLE_ADS_CONVERSION_LABEL'),
            'enabled' => env('GOOGLE_ADS_ENABLED', false),
        ],
        'maps' => [
            'api_key' => env('GOOGLE_MAPS_API_KEY'),
            'enabled' => env('GOOGLE_MAPS_ENABLED', false),
        ],
        'recaptcha' => [
            'site_key' => env('GOOGLE_RECAPTCHA_SITE_KEY'),
            'secret_key' => env('GOOGLE_RECAPTCHA_SECRET_KEY'),
            'enabled' => env('GOOGLE_RECAPTCHA_ENABLED', false),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Payment Gateway Services
    |--------------------------------------------------------------------------
    |
    | Configuration for various payment gateways
    |
    */

    'stripe' => [
        'key' => env('STRIPE_KEY'),
        'secret' => env('STRIPE_SECRET'),
        'webhook_secret' => env('STRIPE_WEBHOOK_SECRET'),
        'enabled' => env('STRIPE_ENABLED', false),
    ],

    'paypal' => [
        'client_id' => env('PAYPAL_CLIENT_ID'),
        'client_secret' => env('PAYPAL_CLIENT_SECRET'),
        'webhook_id' => env('PAYPAL_WEBHOOK_ID'),
        'sandbox' => env('PAYPAL_SANDBOX', true),
        'enabled' => env('PAYPAL_ENABLED', false),
    ],

    'square' => [
        'access_token' => env('SQUARE_ACCESS_TOKEN'),
        'location_id' => env('SQUARE_LOCATION_ID'),
        'webhook_signature_key' => env('SQUARE_WEBHOOK_SIGNATURE_KEY'),
        'sandbox' => env('SQUARE_SANDBOX', true),
        'enabled' => env('SQUARE_ENABLED', false),
    ],

    'razorpay' => [
        'key_id' => env('RAZORPAY_KEY_ID'),
        'key_secret' => env('RAZORPAY_KEY_SECRET'),
        'webhook_secret' => env('RAZORPAY_WEBHOOK_SECRET'),
        'enabled' => env('RAZORPAY_ENABLED', false),
    ],

    'mollie' => [
        'key' => env('MOLLIE_KEY'),
        'api_key' => env('MOLLIE_API_KEY'),
        'webhook_secret' => env('MOLLIE_WEBHOOK_SECRET'),
        'enabled' => env('MOLLIE_ENABLED', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Shipping Services
    |--------------------------------------------------------------------------
    |
    | Configuration for shipping carriers and services
    |
    */

    'shipping' => [
        'ups' => [
            'access_key' => env('UPS_ACCESS_KEY'),
            'username' => env('UPS_USERNAME'),
            'password' => env('UPS_PASSWORD'),
            'account_number' => env('UPS_ACCOUNT_NUMBER'),
            'enabled' => env('UPS_ENABLED', false),
        ],
        'fedex' => [
            'key' => env('FEDEX_KEY'),
            'password' => env('FEDEX_PASSWORD'),
            'account_number' => env('FEDEX_ACCOUNT_NUMBER'),
            'meter_number' => env('FEDEX_METER_NUMBER'),
            'enabled' => env('FEDEX_ENABLED', false),
        ],
        'dhl' => [
            'api_key' => env('DHL_API_KEY'),
            'api_secret' => env('DHL_API_SECRET'),
            'account_number' => env('DHL_ACCOUNT_NUMBER'),
            'enabled' => env('DHL_ENABLED', false),
        ],
        'usps' => [
            'user_id' => env('USPS_USER_ID'),
            'password' => env('USPS_PASSWORD'),
            'enabled' => env('USPS_ENABLED', false),
        ],
        'from' => [
            'name' => env('SHIPPING_FROM_NAME', config('app.name')),
            'company' => env('SHIPPING_FROM_COMPANY'),
            'address_line_1' => env('SHIPPING_FROM_ADDRESS_LINE_1'),
            'address_line_2' => env('SHIPPING_FROM_ADDRESS_LINE_2'),
            'city' => env('SHIPPING_FROM_CITY'),
            'state' => env('SHIPPING_FROM_STATE'),
            'postal_code' => env('SHIPPING_FROM_POSTAL_CODE'),
            'country' => env('SHIPPING_FROM_COUNTRY', 'US'),
            'phone' => env('SHIPPING_FROM_PHONE'),
            'email' => env('SHIPPING_FROM_EMAIL'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Tax Services
    |--------------------------------------------------------------------------
    |
    | Configuration for tax calculation services
    |
    */

    'tax' => [
        'taxjar' => [
            'api_key' => env('TAXJAR_API_KEY'),
            'enabled' => env('TAXJAR_ENABLED', false),
        ],
        'avalara' => [
            'account_id' => env('AVALARA_ACCOUNT_ID'),
            'license_key' => env('AVALARA_LICENSE_KEY'),
            'environment' => env('AVALARA_ENVIRONMENT', 'sandbox'),
            'enabled' => env('AVALARA_ENABLED', false),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Currency Exchange Services
    |--------------------------------------------------------------------------
    |
    | Configuration for currency exchange rate services
    |
    */

    'currency' => [
        'exchange_rate_api' => [
            'key' => env('EXCHANGE_RATE_API_KEY'),
            'provider' => env('EXCHANGE_RATE_PROVIDER', 'exchangerate_api'),
            'enabled' => env('EXCHANGE_RATE_API_ENABLED', false),
        ],
        'open_exchange_rates' => [
            'app_id' => env('OPEN_EXCHANGE_RATES_APP_ID'),
            'enabled' => env('OPEN_EXCHANGE_RATES_ENABLED', false),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Email Marketing Services
    |--------------------------------------------------------------------------
    |
    | Configuration for email marketing and newsletter services
    |
    */

    'mailchimp' => [
        'api_key' => env('MAILCHIMP_API_KEY'),
        'server_prefix' => env('MAILCHIMP_SERVER_PREFIX'),
        'list_id' => env('MAILCHIMP_LIST_ID'),
        'enabled' => env('MAILCHIMP_ENABLED', false),
    ],

    'sendinblue' => [
        'api_key' => env('SENDINBLUE_API_KEY'),
        'list_id' => env('SENDINBLUE_LIST_ID'),
        'enabled' => env('SENDINBLUE_ENABLED', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | SMS Services
    |--------------------------------------------------------------------------
    |
    | Configuration for SMS and notification services
    |
    */

    'twilio' => [
        'sid' => env('TWILIO_SID'),
        'token' => env('TWILIO_TOKEN'),
        'from' => env('TWILIO_FROM'),
        'enabled' => env('TWILIO_ENABLED', false),
    ],

    'vonage' => [
        'key' => env('VONAGE_KEY'),
        'secret' => env('VONAGE_SECRET'),
        'from' => env('VONAGE_FROM'),
        'enabled' => env('VONAGE_ENABLED', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Storage Services
    |--------------------------------------------------------------------------
    |
    | Configuration for cloud storage services
    |
    */

    'aws' => [
        'access_key_id' => env('AWS_ACCESS_KEY_ID'),
        'secret_access_key' => env('AWS_SECRET_ACCESS_KEY'),
        'default_region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
        'bucket' => env('AWS_BUCKET'),
        'use_path_style_endpoint' => env('AWS_USE_PATH_STYLE_ENDPOINT', false),
        'endpoint' => env('AWS_ENDPOINT'),
    ],

    'do_spaces' => [
        'key' => env('DO_SPACES_KEY'),
        'secret' => env('DO_SPACES_SECRET'),
        'region' => env('DO_SPACES_REGION', 'nyc3'),
        'bucket' => env('DO_SPACES_BUCKET'),
        'endpoint' => env('DO_SPACES_ENDPOINT'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Analytics Services
    |--------------------------------------------------------------------------
    |
    | Configuration for analytics and monitoring services
    |
    */

    'hotjar' => [
        'site_id' => env('HOTJAR_SITE_ID'),
        'enabled' => env('HOTJAR_ENABLED', false),
    ],

    'mixpanel' => [
        'token' => env('MIXPANEL_TOKEN'),
        'enabled' => env('MIXPANEL_ENABLED', false),
    ],

    'segment' => [
        'write_key' => env('SEGMENT_WRITE_KEY'),
        'enabled' => env('SEGMENT_ENABLED', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | CDN Services
    |--------------------------------------------------------------------------
    |
    | Configuration for CDN services
    |
    */

    'cloudflare' => [
        'api_token' => env('CLOUDFLARE_API_TOKEN'),
        'zone_id' => env('CLOUDFLARE_ZONE_ID'),
        'enabled' => env('CLOUDFLARE_ENABLED', false),
    ],

    'fastly' => [
        'api_key' => env('FASTLY_API_KEY'),
        'service_id' => env('FASTLY_SERVICE_ID'),
        'enabled' => env('FASTLY_ENABLED', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Search Services
    |--------------------------------------------------------------------------
    |
    | Configuration for search services
    |
    */

    'algolia' => [
        'id' => env('ALGOLIA_APP_ID'),
        'secret' => env('ALGOLIA_SECRET'),
        'enabled' => env('ALGOLIA_ENABLED', false),
    ],

    'elasticsearch' => [
        'hosts' => explode(',', env('ELASTICSEARCH_HOSTS', 'localhost:9200')),
        'enabled' => env('ELASTICSEARCH_ENABLED', false),
    ],

    'meilisearch' => [
        'host' => env('MEILISEARCH_HOST', 'http://localhost:7700'),
        'key' => env('MEILISEARCH_KEY'),
        'enabled' => env('MEILISEARCH_ENABLED', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache Services
    |--------------------------------------------------------------------------
    |
    | Configuration for external cache services
    |
    */

    'redis' => [
        'client' => env('REDIS_CLIENT', 'phpredis'),
        'options' => [
            'cluster' => env('REDIS_CLUSTER', 'redis'),
            'prefix' => env('REDIS_PREFIX', \Illuminate\Support\Str::slug(env('APP_NAME', 'laravel'), '_').'_database_'),
        ],
        'default' => [
            'url' => env('REDIS_URL'),
            'host' => env('REDIS_HOST', '127.0.0.1'),
            'username' => env('REDIS_USERNAME'),
            'password' => env('REDIS_PASSWORD'),
            'port' => env('REDIS_PORT', '6379'),
            'database' => env('REDIS_DB', '0'),
        ],
        'cache' => [
            'url' => env('REDIS_URL'),
            'host' => env('REDIS_HOST', '127.0.0.1'),
            'username' => env('REDIS_USERNAME'),
            'password' => env('REDIS_PASSWORD'),
            'port' => env('REDIS_PORT', '6379'),
            'database' => env('REDIS_CACHE_DB', '1'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Queue Services
    |--------------------------------------------------------------------------
    |
    | Configuration for queue services
    |
    */

    'sqs' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'prefix' => env('SQS_PREFIX', 'https://sqs.us-east-1.amazonaws.com/your-account-id'),
        'queue' => env('SQS_QUEUE', 'default'),
        'suffix' => env('SQS_SUFFIX'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'rabbitmq' => [
        'host' => env('RABBITMQ_HOST', 'localhost'),
        'port' => env('RABBITMQ_PORT', 5672),
        'login' => env('RABBITMQ_LOGIN', 'guest'),
        'password' => env('RABBITMQ_PASSWORD', 'guest'),
        'vhost' => env('RABBITMQ_VHOST', '/'),
    ],

];
