<?php

namespace nickurt\AbuseIpDb;

class ServiceProvider extends \Illuminate\Support\ServiceProvider
{
    /**
     * Bootstrap the application events.
     *
     * @return void
     */
    public function boot()
    {
        $this->loadTranslationsFrom(__DIR__ . '/../src/Resources/Lang', 'abuseipdb');

        $this->publishes([
            __DIR__ . '/../config/abuseipdb.php' => config_path('abuseipdb.php'),
            __DIR__ . '/../src/Resources/Lang' => resource_path('lang/vendor/abuseipdb'),
        ], 'config');
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return ['nickurt\AbuseIpDb\AbuseIpDb', 'AbuseIpDb'];
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton('nickurt\AbuseIpDb\AbuseIpDb', function ($app) {
            $a = new AbuseIpDb();
            $a->setApiKey(config('abuseipdb.api_key'));

            return $a;
        });

        $this->app->alias('nickurt\AbuseIpDb\AbuseIpDb', 'AbuseIpDb');
    }
}
