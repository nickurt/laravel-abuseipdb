<?php

namespace nickurt\AbuseIpDb\Tests;

use Orchestra\Testbench\TestCase;
use AbuseIpDb;
use Event;
use Validator;

class AbuseIpDbTest extends TestCase
{
    /** @test */
    public function it_can_get_default_values()
    {
        $abuseIpDb = new \nickurt\AbuseIpDb\AbuseIpDb();

        $this->assertSame('https://www.abuseipdb.com', $abuseIpDb->getApiUrl());
        $this->assertNull($abuseIpDb->getApiKey());
        $this->assertNull($abuseIpDb->getIp());
        $this->assertSame(30, $abuseIpDb->getDays());
    }

    /** @test */
    public function it_can_set_custom_values()
    {
        $abuseIpDb = (new \nickurt\AbuseIpDb\AbuseIpDb())
            ->setApiUrl('https://internal.abuseipdb.com')
            ->setIp('185.38.14.171')
            ->setDays(100);

        $this->assertSame('https://internal.abuseipdb.com', $abuseIpDb->getApiUrl());
        $this->assertSame('185.38.14.171', $abuseIpDb->getIp());
        $this->assertSame(100, $abuseIpDb->getDays());
    }

    /** @test */
    public function it_can_work_with_container()
    {
        $this->assertInstanceOf(\nickurt\AbuseIpDb\AbuseIpDb::class, $this->app['AbuseIpDb']);
    }

    /** @test */
    public function it_can_work_with_facade()
    {
        $this->assertSame('nickurt\AbuseIpDb\Facade', (new \ReflectionClass(AbuseIpDb::class))->getName());

        $this->assertSame('https://www.abuseipdb.com', AbuseIpDb::getApiUrl());
        $this->assertNull(AbuseIpDb::getIp());;
        $this->assertSame(30, AbuseIpDb::getDays());
    }

    /** @test */
    public function it_can_work_with_helper()
    {
        $this->assertTrue(function_exists('abuseipdb'));

        $this->assertInstanceOf(\nickurt\AbuseIpDb\AbuseIpDb::class, abuseipdb());
    }

    /** @test */
    public function it_will_fire_an_event_by_valid_spam()
    {
        Event::fake();

        $abuseIpDb = (new \nickurt\AbuseIpDb\AbuseIpDb());

        $isSpamIp = $abuseIpDb->setIp('185.38.14.171')->setDays(200)->isSpamIp();

        Event::assertDispatched(\nickurt\AbuseIpDb\Events\IsSpamIp::class, function ($e) use ($abuseIpDb) {
            return $e->ip === $abuseIpDb->getIp();
        });
    }

    /** @test */
    public function it_will_not_fire_an_event_by_invalid_spam()
    {
        Event::fake();

        $abuseIpDb = (new \nickurt\AbuseIpDb\AbuseIpDb());

        $isSpamIp = $abuseIpDb->setIp('192.168.200.200')->setDays(10)->isSpamIp();

        Event::assertNotDispatched(\nickurt\AbuseIpDb\Events\IsSpamIp::class);
    }

    /**
     * @test
     * @expectedException \nickurt\AbuseIpDb\Exception\MalformedURLException
     */
    public function it_will_throw_malformed_url_exception()
    {
        $abuseIpDb = (new \nickurt\AbuseIpDb\AbuseIpDb())
            ->setApiUrl('malformed_url');
    }

    /** @test */
    public function it_will_work_with_validation_rule_is_spam_ip()
    {
        $val1 = Validator::make(['aip' => 'aip'], ['aip' => ['required', new \nickurt\AbuseIpDb\Rules\IsSpamIp('185.38.14.171', 200)]]);

        $this->assertFalse($val1->passes());
        $this->assertSame(1, count($val1->messages()->get('aip')));
        $this->assertSame('It is currently not possible to register with your specified information, please try later again', $val1->messages()->first('aip'));

        $val2 = Validator::make(['aip' => 'aip'], ['aip' => ['required', new \nickurt\AbuseIpDb\Rules\IsSpamIp('192.168.200.200', 10)]]);

        $this->assertTrue($val2->passes());
        $this->assertSame(0, count($val2->messages()->get('aip')));
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
}