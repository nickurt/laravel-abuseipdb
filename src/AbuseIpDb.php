<?php

namespace nickurt\AbuseIpDb;

use \GuzzleHttp\Client;
use \nickurt\AbuseIpDb\Exception\MalformedURLException;

class AbuseIpDb
{
    /**
     * @var string
     */
    protected $apiUrl = 'https://www.abuseipdb.com';

    /**
     * @var
     */
    protected $apiKey;

    /**
     * @var
     */
    protected $ip;

    /**
     * @var
     */
    protected $days = 30;

    /**
     * @return string
     */
    public function getApiUrl()
    {
        return $this->apiUrl;
    }

    /**
     * @param $apiUrl
     * @return $this
     */
    public function setApiUrl($apiUrl)
    {
        if (filter_var($apiUrl, FILTER_VALIDATE_URL) === false) {
            throw new MalformedURLException();
        }

        $this->apiUrl = $apiUrl;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getApiKey()
    {
        return $this->apiKey;
    }

    /**
     * @param $apiKey
     * @return $this
     */
    public function setApiKey($apiKey)
    {
        $this->apiKey = $apiKey;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getIp()
    {
        return $this->ip;
    }

    /**
     * @param $ip
     * @return $this
     */
    public function setIp($ip)
    {
        $this->ip = $ip;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getDays()
    {
        return $this->days;
    }

    /**
     * @param $days
     * @return $this
     */
    public function setDays($days)
    {
        $this->days = $days;
        return $this;
    }
    /**
     * @return bool
     */
    public function IsSpamIp()
    {
        $response = $this->getResponseData(
            sprintf('%s/check/%s/json?key=%s&days=%d',
                $this->getApiUrl(),
                $this->getIp(),
                $this->getApiKey(),
                $this->getDays()
            ));

        $result = json_decode((string) $response->getBody());

        if((bool) (count($result) > 0)) {
            event(new \nickurt\AbuseIpDb\Events\IsSpamIp($this->getIp()));

            return true;
        }

        return false;
    }

    /**
     * @param $url
     * @return \Psr\Http\Message\ResponseInterface
     */
    protected function getResponseData($url)
    {
        return (new Client())->get($url);
    }
}
