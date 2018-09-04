## Laravel AbuseIpDb

### Installation
Install this package with composer:
```
composer require nickurt/laravel-abuseipdb
```

Add the provider to config/app.php file

```php
'nickurt\AbuseIpDb\ServiceProvider',
```

and the facade in the file

```php
'AbuseIpDb' => 'nickurt\AbuseIpDb\Facade',
```

Copy the config files for the AbuseIpDb-plugin

```
php artisan vendor:publish --provider="nickurt\AbuseIpDb\ServiceProvider" --tag="config"
```
### Configuration
The AbuseIpDb information can be set with environment values in the .env file (or directly in the config/abusedbip.php file)
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
$isSpamIp = (new \nickurt\AbuseIpDb\AbuseIpDb())
	->setIp('8.8.8.8')
	->isSpamIp();
	
// ...	
$isSpamIp = abuseipdb()
    ->setIp('8.8.8.8')
    ->isSpamIp();
```
#### Events
You can listen to the `IsSpamIp` event, e.g. if you want to log the `IsSpamIp`-requests in your application
##### IsSpamIp Event
This event will be fired when the request-ip is above the frequency of sending spam
`nickurt\AbuseIpDb\Events\IsSpamIp`
### Tests
```sh
phpunit
```
- - - 