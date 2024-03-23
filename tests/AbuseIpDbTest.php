<?php

namespace nickurt\AbuseIpDb\tests;

use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use nickurt\AbuseIpDb\Events\IsSpamIp;
use nickurt\AbuseIpDb\Exception\AbuseIpDbException;
use nickurt\AbuseIpDb\Exception\MalformedURLException;
use nickurt\AbuseIpDb\Facade;
use nickurt\AbuseIpDb\Facade as AbuseIpDb;
use nickurt\AbuseIpDb\ServiceProvider;
use Orchestra\Testbench\TestCase;

class AbuseIpDbTest extends TestCase
{
    /** @var \nickurt\AbuseIpDb\AbuseIpDb */
    protected $abuseIpDb;

    public function setUp(): void
    {
        parent::setUp();

        $this->abuseIpDb = AbuseIpDb::getFacadeRoot();
    }

    public function test_it_can_report_a_spam_ip()
    {
        Http::fake(['https://api.abuseipdb.com/api/v2/report?ip=127.0.0.1&categories=3%2C4%2C5&comment=' => Http::response('{"data":{"ipAddress":"127.0.0.1","abuseConfidenceScore":0}}')]);

        $this->assertSame(0, $this->abuseIpDb->setIp('39.6.25.118')->reportIp('3,4,5', '127.0.0.1'));
        $this->assertSame('127.0.0.1', $this->abuseIpDb->getIp());
    }

    public function test_it_can_return_the_default_values()
    {
        $this->assertSame('abcdefghijklmnopqrstuvwxyz', $this->abuseIpDb->getApiKey());
        $this->assertSame('https://api.abuseipdb.com/api/v2', $this->abuseIpDb->getApiUrl());
        $this->assertSame(10, $this->abuseIpDb->getCacheTtl());
        $this->assertSame(30, $this->abuseIpDb->getDays());
        $this->assertNull($this->abuseIpDb->getIp());
        $this->assertSame(100, $this->abuseIpDb->getSpamThreshold());
    }

    public function test_it_can_set_a_custom_value_for_the_api_key()
    {
        $this->abuseIpDb->setApiKey('zyxwvutsrqponmlkjihgfedcba');

        $this->assertSame('zyxwvutsrqponmlkjihgfedcba', $this->abuseIpDb->getApiKey());
    }

    public function test_it_can_set_a_custom_value_for_the_api_url()
    {
        $this->abuseIpDb->setApiUrl('https://api-ppe.abuseipdb.com/api/v2');

        $this->assertSame('https://api-ppe.abuseipdb.com/api/v2', $this->abuseIpDb->getApiUrl());
    }

    public function test_it_can_set_a_custom_value_for_the_cache_ttl()
    {
        $this->abuseIpDb->setCacheTtl(180);

        $this->assertSame(180, $this->abuseIpDb->getCacheTtl());
    }

    public function test_it_can_set_a_custom_value_for_the_ip()
    {
        $this->abuseIpDb->setIp('8.8.8.8');

        $this->assertSame('8.8.8.8', $this->abuseIpDb->getIp());
    }

    public function test_it_can_set_a_custom_value_for_the_max_days()
    {
        $this->abuseIpDb->setDays(90);

        $this->assertSame(90, $this->abuseIpDb->getDays());
    }

    public function test_it_can_set_a_custom_value_for_the_spam_threshold()
    {
        $this->abuseIpDb->setSpamThreshold(50);

        $this->assertSame(50, $this->abuseIpDb->getSpamThreshold());
    }

    public function test_it_can_work_with_helper_function()
    {
        $this->assertInstanceOf(\nickurt\AbuseIpDb\AbuseIpDb::class, abuseipdb());
    }

    public function test_it_will_fire_is_spam_ip_event_by_is_spam_ip_validation_rule()
    {
        Event::fake();

        Http::fake(['https://api.abuseipdb.com/api/v2/check?ipAddress=118.25.6.39&maxAgeInDays=30' => Http::response('{"data":{"ipAddress":"118.25.6.39","isPublic":true,"ipVersion":4,"isWhitelisted":false,"abuseConfidenceScore":36,"countryCode":"CN","usageType":"Data Center\/Web Hosting\/Transit","isp":"Tencent Cloud Computing (Beijing) Co. Ltd","domain":"tencent.com","totalReports":9,"numDistinctUsers":5,"lastReportedAt":"2019-07-04T17:15:00+01:00"}}')]);

        $rule = new \nickurt\AbuseIpDb\Rules\IsSpamIp('118.25.6.39', 30, 35);

        $this->assertFalse($rule->passes('aip', 'aip'));
        $this->assertSame('It is currently not possible to register with your specified information, please try later again', $rule->message());
        $this->assertSame('118.25.6.39', $this->abuseIpDb->getIp());

        Event::assertDispatched(IsSpamIp::class, function ($e) {
            $this->assertSame('118.25.6.39', $e->ip);
            $this->assertSame(36, $e->frequency);

            return true;
        });
    }

    public function test_it_will_fire_is_spam_ip_event_by_spam_ip()
    {
        Event::fake();

        Http::fake(['https://api.abuseipdb.com/api/v2/check?ipAddress=118.25.6.39&maxAgeInDays=30' => Http::response('{"data":{"ipAddress":"118.25.6.39","isPublic":true,"ipVersion":4,"isWhitelisted":false,"abuseConfidenceScore":36,"countryCode":"CN","usageType":"Data Center\/Web Hosting\/Transit","isp":"Tencent Cloud Computing (Beijing) Co. Ltd","domain":"tencent.com","totalReports":9,"numDistinctUsers":5,"lastReportedAt":"2019-07-04T17:15:00+01:00"}}')]);

        $this->assertTrue($this->abuseIpDb->setDays(30)->setSpamThreshold(35)->setIp('39.6.25.118')->isSpamIp('118.25.6.39'));
        $this->assertSame('118.25.6.39', $this->abuseIpDb->getIp());

        Event::assertDispatched(IsSpamIp::class, function ($e) {
            $this->assertSame('118.25.6.39', $e->ip);
            $this->assertSame(36, $e->frequency);

            return true;
        });
    }

    public function test_it_will_not_fire_is_spam_ip_by_valid_ip()
    {
        Event::fake();

        Http::fake(['https://api.abuseipdb.com/api/v2/check?ipAddress=118.25.6.39&maxAgeInDays=30' => Http::response('{"data":{"ipAddress":"118.25.6.39","isPublic":true,"ipVersion":4,"isWhitelisted":false,"abuseConfidenceScore":36,"countryCode":"CN","usageType":"Data Center\/Web Hosting\/Transit","isp":"Tencent Cloud Computing (Beijing) Co. Ltd","domain":"tencent.com","totalReports":9,"numDistinctUsers":5,"lastReportedAt":"2019-07-04T17:15:00+01:00"}}')]);

        $this->assertFalse($this->abuseIpDb->setSpamThreshold(37)->setIp('118.25.6.39')->isSpamIp());

        Event::assertNotDispatched(IsSpamIp::class);
    }

    public function test_it_will_not_fire_is_spam_ip_event_by_is_spam_ip_validation_rule_by_valid_ip()
    {
        Event::fake();

        Http::fake(['https://api.abuseipdb.com/api/v2/check?ipAddress=118.25.6.39&maxAgeInDays=30' => Http::response('{"data":{"ipAddress":"118.25.6.39","isPublic":true,"ipVersion":4,"isWhitelisted":false,"abuseConfidenceScore":36,"countryCode":"CN","usageType":"Data Center\/Web Hosting\/Transit","isp":"Tencent Cloud Computing (Beijing) Co. Ltd","domain":"tencent.com","totalReports":9,"numDistinctUsers":5,"lastReportedAt":"2019-07-04T17:15:00+01:00"}}')]);

        $rule = new \nickurt\AbuseIpDb\Rules\IsSpamIp('118.25.6.39', 30, 37);

        $this->assertTrue($rule->passes('aip', 'aip'));

        Event::assertNotDispatched(IsSpamIp::class);
    }

    public function test_it_will_throw_exception_by_invalid_json_response()
    {
        Http::fake(['https://api.abuseipdb.com/api/v2/check?ipAddress=127.0.0.1&maxAgeInDays=30' => Http::response(null)]);

        $this->expectException(AbuseIpDbException::class);
        $this->expectExceptionMessage('abuseipdb returned an invalid json response: "".');

        $this->abuseIpDb->setIp('127.0.0.1');

        $this->abuseIpDb->isSpamIp();
    }

    public function test_it_will_throw_exception_by_authentication_failed()
    {
        Http::fake(['https://api.abuseipdb.com/api/v2/check?ipAddress=127.0.0.1&maxAgeInDays=30' => Http::response('{"errors":[{"detail":"Authentication failed. You are either missing your API key or it is incorrect. Note: The APIv2 key differs from the APIv1 key.","status":401}]}')]);

        $this->expectException(AbuseIpDbException::class);
        $this->expectExceptionMessage('Authentication failed. You are either missing your API key or it is incorrect. Note: The APIv2 key differs from the APIv1 key.');

        $this->abuseIpDb->setIp('127.0.0.1');

        $this->abuseIpDb->isSpamIp();
    }

    public function test_it_will_throw_exception_by_invalid_ip_address()
    {
        Http::fake(['https://api.abuseipdb.com/api/v2/check?ipAddress=x.x.x.x&maxAgeInDays=30' => Http::response('{"errors":[{"detail":"The ip address must be a valid IPv4 or IPv6 address (e.g. 8.8.8.8 or 2001:4860:4860::8888).","status":422}]}')]);

        $this->expectException(AbuseIpDbException::class);
        $this->expectExceptionMessage('The ip address must be a valid IPv4 or IPv6 address (e.g. 8.8.8.8 or 2001:4860:4860::8888).');

        $this->abuseIpDb->setIp('x.x.x.x')->isSpamIp();
    }

    public function test_it_will_throw_exception_by_reporting_the_same_ip_address_within_15_minutes()
    {
        Http::fake(['https://api.abuseipdb.com/api/v2/report?ip=127.0.0.1&categories=3%2C4%2C5&comment=' => Http::response('{"errors":[{"detail":"You can only report the same IP address (`127.0.0.1`) once in 15 minutes.","status":429,"source":{"parameter":"ip"}}]}')]);

        $this->expectException(AbuseIpDbException::class);
        $this->expectExceptionMessage('You can only report the same IP address (`127.0.0.1`) once in 15 minutes.');

        $this->assertSame(0, $this->abuseIpDb->reportIp('3,4,5', '127.0.0.1'));
    }

    public function test_it_will_throw_malformed_url_exception()
    {
        $this->expectException(MalformedURLException::class);

        $this->abuseIpDb->setApiUrl('malformed_url');
    }

    /**
     * Define environment setup.
     *
     * @param  Application  $app
     * @return void
     */
    protected function getEnvironmentSetUp($app)
    {
        $app['config']->set('abuseipdb.api_key', 'abcdefghijklmnopqrstuvwxyz');
    }

    /**
     * @param  Application  $app
     * @return array
     */
    protected function getPackageAliases($app)
    {
        return [
            'Cache' => Cache::class,
            'AbuseIpDb' => Facade::class,
        ];
    }

    /**
     * @param  Application  $app
     * @return array
     */
    protected function getPackageProviders($app)
    {
        return [
            ServiceProvider::class,
        ];
    }
}
