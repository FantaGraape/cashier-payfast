<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Payfast Merchant ID
    |--------------------------------------------------------------------------
    |
    | This can be found either from the Payfast Sandbox or the Merchant settings in your 
    | Payfast Portal.
    |
    */
    'merchant_id' => env('PAYFAST_MERCHANT_ID', '10026426'),
    /*
    |--------------------------------------------------------------------------
    | Payfast Merchant KEY
    |--------------------------------------------------------------------------
    |
    | This can be found either from the Payfast Sandbox or the Merchant settings in your 
    | Payfast Portal.
    |
    */
    'merchant_key' => env('PAYFAST_MERCHANT_KEY', 'nkoq82zstric8'),
    /*
    |--------------------------------------------------------------------------
    | Payfast Passphrase
    |--------------------------------------------------------------------------
    |
    | This must be used for working with subscriptions for payfast
    | This can be found either from the Payfast Sandbox or the Merchant settings in your 
    | Payfast Portal.
    |
    */
    'passphrase' => env('PAYFAST_PASSPHRASE', 'PfN9Xxxh5TUK4xwFs'),
    'proxy' => env('PAYFAST_PROXY'),
    /*
    |--------------------------------------------------------------------------
    | Payfast Sandbox
    |--------------------------------------------------------------------------
    |
    | This option allows you to toggle between the Payfast live environment
    | and its sandboxed environment. This feature is available publicly.
    |
    */

    'sandbox' => env('PAYFAST_TESTMODE', true),
    /*
    |--------------------------------------------------------------------------
    | Cashier Path
    |--------------------------------------------------------------------------
    |
    | This is the base URI path where Cashier's views, such as the webhook
    | route, will be available. You're free to tweak this path based on
    | the needs of your particular application or design preferences.
    |
    */

    'path' => env('CASHIER_PATH', 'payfast'),

    /*
    |--------------------------------------------------------------------------
    | Cashier Webhook
    |--------------------------------------------------------------------------
    |
    | This is the base URI where webhooks from Payfast will be sent. The URL
    | built into Cashier Payfast is used by default; however, you can add
    | a custom URL when required for any application testing purposes.
    |
    */
    'notify_url' => env('PAYFAST_NOTIFY_URL', 'https://4eea-102-165-226-14.sa.ngrok.io' . '/payfast/webhook'),
    'return_url' => env('PAYFAST_RETURN_URL', 'https://ellis-systems.tech/dashboard'),
    /* testing for removal */
    'webhook' => env('CASHIER_WEBHOOK'),

    /*
    |--------------------------------------------------------------------------
    | Currency
    |--------------------------------------------------------------------------
    |
    | This is the default currency that will be used when generating charges
    | from your application. Of course, you are welcome to use any of the
    | various world currencies that are currently supported via Payfast, currently only ZAR.
    |
    */

    'currency' => env('CASHIER_CURRENCY', 'ZAR'),

    /*
    |--------------------------------------------------------------------------
    | Currency Locale
    |--------------------------------------------------------------------------
    |
    | This is the default locale in which your money values are formatted in
    | for display. To utilize other locales besides the default en locale
    | verify you have the "intl" PHP extension installed on the system.
    |
    */

    'currency_locale' => env('CASHIER_CURRENCY_LOCALE', 'en'),



];
