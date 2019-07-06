<?php

namespace nickurt\AbuseIpDb\Tests;

use Orchestra\Testbench\TestCase;
use AbuseIpDb;
use Event;
use Validator;

class AbuseIpDbTest extends TestCase
{
    /**
     * Define environment setup.
     *
     * @param \Illuminate\Foundation\Application $app
     *
     * @return void
     */
    protected function getEnvironmentSetUp($app)
    {
        $app['config']->set('abuseipdb.api_key', 'abcdefghijklmnopqrstuvwxyz');
    }

    /**
     * @param \Illuminate\Foundation\Application $app
     * @return array
     */
    protected function getPackageAliases($app)
    {
        return [
            'Event' => \Illuminate\Support\Facades\Event::class,
            'AbuseIpDb' => \nickurt\AbuseIpDb\Facade::class
        ];
    }

    /**
     * @param \Illuminate\Foundation\Application $app
     * @return array
     */
    protected function getPackageProviders($app)
    {
        return [
            \nickurt\AbuseIpDb\ServiceProvider::class
        ];
    }

    /** @test */
    public function it_can_get_the_http_client()
    {
        $this->assertInstanceOf(\GuzzleHttp\Client::class, app('AbuseIpDb')->getClient());
    }

    /** @test */
    public function it_can_report_a_spam_ip()
    {
        $httpClient = new \GuzzleHttp\Client([
            'handler' => $mockHandler = new \GuzzleHttp\Handler\MockHandler(),
        ]);

        $mockHandler->append(new \GuzzleHttp\Psr7\Response(200, [], '{"data":{"ipAddress":"127.0.0.1","abuseConfidenceScore":0}}'));

        $this->assertSame(0, \AbuseIpDb::setClient($httpClient)->reportIp('3,4,5', '127.0.0.1'));
    }

    /** @test */
    public function it_can_return_the_default_values()
    {
        $abuseIpDb = app('AbuseIpDb');

        $this->assertSame('abcdefghijklmnopqrstuvwxyz', $abuseIpDb->getApiKey());
        $this->assertSame('https://api.abuseipdb.com/api/v2', $abuseIpDb->getApiUrl());
        $this->assertSame(10, $abuseIpDb->getCacheTtl());
        $this->assertSame(30, $abuseIpDb->getDays());
        $this->assertNull($abuseIpDb->getIp());
        $this->assertSame(100, $abuseIpDb->getSpamThreshold());
    }

    /** @test */
    public function it_can_set_a_custom_value_for_the_api_key()
    {
        $abuseIpDb = app('AbuseIpDb')->setApiKey('zyxwvutsrqponmlkjihgfedcba');

        $this->assertSame('zyxwvutsrqponmlkjihgfedcba', $abuseIpDb->getApiKey());
    }

    /** @test */
    public function it_can_set_a_custom_value_for_the_api_url()
    {
        $abuseIpDb = app('AbuseIpDb')->setApiUrl('https://api-ppe.abuseipdb.com/api/v2');

        $this->assertSame('https://api-ppe.abuseipdb.com/api/v2', $abuseIpDb->getApiUrl());
    }

    /** @test */
    public function it_can_set_a_custom_value_for_the_cache_ttl()
    {
        $abuseIpDb = app('AbuseIpDb')->setCacheTtl(180);

        $this->assertSame(180, $abuseIpDb->getCacheTtl());
    }

    /** @test */
    public function it_can_set_a_custom_value_for_the_ip()
    {
        $abuseIpDb = app('AbuseIpDb')->setIp('8.8.8.8');

        $this->assertSame('8.8.8.8', $abuseIpDb->getIp());
    }

    /** @test */
    public function it_can_set_a_custom_value_for_the_max_days()
    {
        $abuseIpDb = app('AbuseIpDb')->setDays(90);

        $this->assertSame(90, $abuseIpDb->getDays());
    }

    /** @test */
    public function it_can_set_a_custom_value_for_the_spam_threshold()
    {
        $abuseIpDb = app('AbuseIpDb')->setSpamThreshold(50);

        $this->assertSame(50, $abuseIpDb->getSpamThreshold());
    }

    /** @test */
    public function it_can_work_with_helper_function()
    {
        $this->assertInstanceOf(\nickurt\AbuseIpDb\AbuseIpDb::class, abuseipdb());
    }


    /** @test */
    public function it_will_fire_is_spam_ip_event_by_is_spam_ip_validation_rule()
    {
        \Event::fake();

        $httpClient = new \GuzzleHttp\Client([
            'handler' => $mockHandler = new \GuzzleHttp\Handler\MockHandler(),
        ]);;

        $mockHandler->append(new \GuzzleHttp\Psr7\Response(200, [], '{"data":{"ipAddress":"118.25.6.39","isPublic":true,"ipVersion":4,"isWhitelisted":false,"abuseConfidenceScore":36,"countryCode":"CN","usageType":"Data Center\/Web Hosting\/Transit","isp":"Tencent Cloud Computing (Beijing) Co. Ltd","domain":"tencent.com","totalReports":9,"numDistinctUsers":5,"lastReportedAt":"2019-07-04T17:15:00+01:00"}}'));

        app('AbuseIpDb')->setClient($httpClient);

        $validator = Validator::make(['aip' => 'aip'], ['aip' => ['required', new \nickurt\AbuseIpDb\Rules\IsSpamIp('118.25.6.39', 30, 35)]]);

        $this->assertFalse($validator->passes());
        $this->assertSame('It is currently not possible to register with your specified information, please try later again', $validator->messages()->first('aip'));

        \Event::assertDispatched(\nickurt\AbuseIpDb\Events\IsSpamIp::class, function ($e) {
            return $e->ip == '118.25.6.39';
        });
    }

    /** @test */
    public function it_will_fire_is_spam_ip_event_by_spam_ip()
    {
        \Event::fake();

        $httpClient = new \GuzzleHttp\Client([
            'handler' => $mockHandler = new \GuzzleHttp\Handler\MockHandler(),
        ]);;

        $mockHandler->append(new \GuzzleHttp\Psr7\Response(200, [], '{"data":{"ipAddress":"118.25.6.39","isPublic":true,"ipVersion":4,"isWhitelisted":false,"abuseConfidenceScore":36,"countryCode":"CN","usageType":"Data Center\/Web Hosting\/Transit","isp":"Tencent Cloud Computing (Beijing) Co. Ltd","domain":"tencent.com","totalReports":9,"numDistinctUsers":5,"lastReportedAt":"2019-07-04T17:15:00+01:00"}}'));

        $this->assertTrue(\AbuseIpDb::setClient($httpClient)->setSpamThreshold(35)->setIp('118.25.6.39')->isSpamIp());

        \Event::assertDispatched(\nickurt\AbuseIpDb\Events\IsSpamIp::class, function ($e) {
            return $e->ip == '118.25.6.39';
        });
    }

    /** @test */
    public function it_will_not_fire_is_spam_ip_by_valid_ip()
    {
        \Event::fake();

        $httpClient = new \GuzzleHttp\Client([
            'handler' => $mockHandler = new \GuzzleHttp\Handler\MockHandler(),
        ]);

        $mockHandler->append(new \GuzzleHttp\Psr7\Response(200, [], '{"data":{"ipAddress":"118.25.6.39","isPublic":true,"ipVersion":4,"isWhitelisted":false,"abuseConfidenceScore":36,"countryCode":"CN","usageType":"Data Center\/Web Hosting\/Transit","isp":"Tencent Cloud Computing (Beijing) Co. Ltd","domain":"tencent.com","totalReports":9,"numDistinctUsers":5,"lastReportedAt":"2019-07-04T17:15:00+01:00"}}'));

        $this->assertFalse(\AbuseIpDb::setClient($httpClient)->setSpamThreshold(37)->setIp('118.25.6.39')->isSpamIp());

        \Event::assertNotDispatched(\nickurt\AbuseIpDb\Events\IsSpamIp::class);
    }

    /** @test */
    public function it_will_not_fire_is_spam_ip_event_by_is_spam_ip_validation_rule_by_valid_ip()
    {
        \Event::fake();

        $httpClient = new \GuzzleHttp\Client([
            'handler' => $mockHandler = new \GuzzleHttp\Handler\MockHandler(),
        ]);

        $mockHandler->append(new \GuzzleHttp\Psr7\Response(200, [], '{"data":{"ipAddress":"118.25.6.39","isPublic":true,"ipVersion":4,"isWhitelisted":false,"abuseConfidenceScore":36,"countryCode":"CN","usageType":"Data Center\/Web Hosting\/Transit","isp":"Tencent Cloud Computing (Beijing) Co. Ltd","domain":"tencent.com","totalReports":9,"numDistinctUsers":5,"lastReportedAt":"2019-07-04T17:15:00+01:00"}}'));

        app('AbuseIpDb')->setClient($httpClient);

        $validator = Validator::make(['aip' => 'aip'], ['aip' => ['required', new \nickurt\AbuseIpDb\Rules\IsSpamIp('118.25.6.39', 30, 37)]]);

        $this->assertTrue($validator->passes());

        \Event::assertNotDispatched(\nickurt\AbuseIpDb\Events\IsSpamIp::class);
    }

    /** @test */
    public function it_will_throw_exception_by_authentication_failed()
    {
        $this->expectException(\nickurt\AbuseIpDb\Exception\AbuseIpDbException::class);
        $this->expectExceptionMessage('Authentication failed. You are either missing your API key or it is incorrect. Note: The APIv2 key differs from the APIv1 key.');

        $httpClient = new \GuzzleHttp\Client([
            'handler' => $mockHandler = new \GuzzleHttp\Handler\MockHandler(),
        ]);;

        $mockHandler->append(new \GuzzleHttp\Psr7\Response(200, [], '{"errors":[{"detail":"Authentication failed. You are either missing your API key or it is incorrect. Note: The APIv2 key differs from the APIv1 key.","status":401}]}'));

        \AbuseIpDb::setClient($httpClient)->isSpamIp();
    }

    /** @test */
    public function it_will_throw_exception_by_invalid_ip_address()
    {
        $this->expectException(\nickurt\AbuseIpDb\Exception\AbuseIpDbException::class);
        $this->expectExceptionMessage('The ip address must be a valid IPv4 or IPv6 address (e.g. 8.8.8.8 or 2001:4860:4860::8888).');

        $httpClient = new \GuzzleHttp\Client([
            'handler' => $mockHandler = new \GuzzleHttp\Handler\MockHandler(),
        ]);;

        $mockHandler->append(new \GuzzleHttp\Psr7\Response(200, [], '{"errors":[{"detail":"The ip address must be a valid IPv4 or IPv6 address (e.g. 8.8.8.8 or 2001:4860:4860::8888).","status":422}]}'));

        \AbuseIpDb::setClient($httpClient)->setIp('x.x.x.x')->isSpamIp();
    }

    /** @test */
    public function it_will_throw_exception_by_reporting_the_same_ip_address_within_15_minutes()
    {
        $this->expectException(\nickurt\AbuseIpDb\Exception\AbuseIpDbException::class);
        $this->expectExceptionMessage('You can only report the same IP address (`127.0.0.1`) once in 15 minutes.');

        $httpClient = new \GuzzleHttp\Client([
            'handler' => $mockHandler = new \GuzzleHttp\Handler\MockHandler(),
        ]);

        $mockHandler->append(new \GuzzleHttp\Psr7\Response(200, [], '{"errors":[{"detail":"You can only report the same IP address (`127.0.0.1`) once in 15 minutes.","status":429,"source":{"parameter":"ip"}}]}'));

        $this->assertSame(0, \AbuseIpDb::setClient($httpClient)->reportIp('3,4,5', '127.0.0.1'));
    }

    /** @test */
    public function it_will_throw_malformed_url_exception()
    {
        $this->expectException(\nickurt\AbuseIpDb\Exception\MalformedURLException::class);

        \AbuseIpDb::setApiUrl('malformed_url');
    }
}