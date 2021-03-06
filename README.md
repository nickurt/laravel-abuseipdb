## Laravel AbuseIpDb
[![Build Status](https://github.com/nickurt/laravel-abuseipdb/workflows/tests/badge.svg)](https://github.com/nickurt/laravel-abuseipdb/actions)
[![Total Downloads](https://poser.pugx.org/nickurt/laravel-abuseipdb/d/total.svg)](https://packagist.org/packages/nickurt/laravel-abuseipdb)
[![Latest Stable Version](https://poser.pugx.org/nickurt/laravel-abuseipdb/v/stable.svg)](https://packagist.org/packages/nickurt/laravel-abuseipdb)
[![MIT Licensed](https://poser.pugx.org/nickurt/laravel-abuseipdb/license.svg)](LICENSE.md)

### Installation
Install this package with composer:
```
composer require nickurt/laravel-abuseipdb
```

Copy the config files for the AbuseIpDb-plugin

```
php artisan vendor:publish --provider="nickurt\AbuseIpDb\ServiceProvider" --tag="config"
```
### Configuration
The AbuseIpDb information can be set with environment values in the `.env` file (or directly in the `config/abusedbip.php` file)
```
ABUSEIPDB_APIKEY=MY_UNIQUE_APIKEY
```
### Examples

#### Validation Rule - IsSpamIp
You can use a hidden-field `aip` in your Form-Request to validate if the request is valid
```php
$validator = validator()->make(['aip' => 'aip'], ['aip' => [new \nickurt\AbuseIpDb\Rules\IsSpamIp(
    request()->ip(), 100
)]]);
```
The `IsSpamIp` requires a `ip` and an optional `days` parameter to validate the request.
#### Manually Usage - IsSpamIp
```php
$isSpamIp = \AbuseIpDb::setIp('8.8.8.8')->isSpamIp();

// Same
$isSpamIp = abuseipdb()->setIp('8.8.8.8')->isSpamIp();
$isSpamIp = abuseipdb()->isSpamIp('8.8.8.8');

// Cache the result for 10 minutes (default 10 seconds)
abuseipdb()->setCacheTTL(600);

// Lower the required abuse confidence score from 100 (max) to 90%
abuseipdb()->setSpamThreshold(90);

// Report an IP in categories 18 (Brute-Force) and 22 (SSH)
// For categories see https://www.abuseipdb.com/categories
$updatedAbuseConfidence = abuseipdb()->reportIp('18,22', '127.0.0.1', 'SSH login attempts with user root.');

// Catch exceptions
try {
    abuseipdb()->isSpamIp('invalid-ip');
} catch(\nickurt\AbuseIpDb\AbuseIpDbException $exception) {
    dd($exception->getMessage());
    
    // "The ip address must be a valid IPv4 or IPv6 address (e.g. 8.8.8.8 or 2001:4860:4860::8888)."
}

try {
    // Do it twice (happens eg if hacker accesses two invalid urls and each reports this IP)
    // Both commands do the same in just two different ways 
    
    abuseipdb()->setIp('127.0.0.2')->reportIp('18,22');
    abuseipdb()->reportIp('18,22', '127.0.0.2');
} catch(\nickurt\AbuseIpDb\AbuseIpDbException $exception) {
    dd($exception->getMessage());
    
    // "You can only report the same IP address (`127.0.0.2`) once in 15 minutes."
}
```
#### Events
You can listen to the `IsSpamIp` event, e.g. if you want to log the `IsSpamIp`-requests in your application
##### IsSpamIp Event
This event will be fired when the request-ip is above the frequency of sending spam
`nickurt\AbuseIpDb\Events\IsSpamIp`
### Testing
Run the tests with:
```sh
composer test
```
- - - 
