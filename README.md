## Introduction

EllisSystems Cashier payfast provides an expressive, fluent interface to [Payfast's](https://payfast.co.za) subscription billing services. It handles almost all of the boilerplate subscription billing code you are dreading writing. In addition to basic subscription management, Cashier can handle coupons, swapping subscription, subscription "quantities", cancellation grace periods and much more.

## Getting Started

Install the pacakge via composer: (WIP)

```bash
composer require ellissystems/cashier-payfast
```

## Publish Configuration

Publish the config file with:

```bash
php artisan vendor:publish --provider="EllisSystems\Payfast\CashierServiceProvider" --tag="cashier-config"
```

## Migrations

A migration is needed to create Customers, Orders, Receipts and Subscriptions tables:

```bash
php artisan migrate
```

## Example Configuration

`config/cashier.php`:

```php
<?php

return [
    'merchant_id' => env('PAYFAST_MERCHANT_ID', '123456'),
    'merchant_key' => env('PAYFAST_MERCHANT_KEY', '7890000'),
    'passphrase' => env('PAYFAST_PASSPHRASE', 'payfast'),
    'proxy' => env('PAYFAST_PROXY'),
    'sandbox' => env('PAYFAST_TESTMODE', true),
    'return_url' => env('PAYFAST_RETURN_URL', config('app.url') . '/payfast/success'),
    'cancel_url' => env('PAYFAST_CANCEL_URL', config('app.url') . '/payfast/cancel'),
    'notify_url' => env('PAYFAST_NOTIFY_URL', config('app.url') . '/payfast/webhook'),
    'currency' => env('CASHIER_CURRENCY', 'ZAR'),
    'currency_locale' => env('CASHIER_CURRENCY_LOCALE', 'en'),

];
```

## Billable Trait

`app/http/models/user.php`:

Add the billable trait to the user model.

```php
<?php

namespace App\Models;

/* .... */
use EllisSystems\Payfast\Billable;

class User extends Authenticatable
{
    use /* .... */ Billable;

    /* ..... */
}
```

## Create a new order

`app/http/Controllers/PaymentController.php`:

```php
<?php
/* ..... */
 public function store(Request $request)
    {
        $request->validate([
            'amount' => 'required'
        ]);
        $user = $request->user();
        $uuid = $user->newOrder('default', $request['amount'], $request->ip())->withFirstName('FirstName')
        ->withLastName('LastName')
        ->withEmailAddress($user->email)
        ->create();
        return response()->json(['uuid' => $uuid], 200);
    }
/* ...... */
```

## Trigger Payfast Modal

```javascript

```


## Official Documentation

Documentation for Cashier Payfast can be found on the [Laravel website](https://laravel.com/docs/cashier-paddle).

## Contributing

Thank you for considering contributing to Cashier Paddle! You can read the contribution guide [here](.github/CONTRIBUTING.md).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

Please review [our security policy](https://github.com/fantagraape/cashier-payfast/security/policy) on how to report security vulnerabilities.

## License

Laravel Cashier Payfast is open-sourced software licensed under the [MIT license](LICENSE.md).
