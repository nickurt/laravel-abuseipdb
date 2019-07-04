<?php

namespace nickurt\AbuseIpDb;

use \GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use \nickurt\AbuseIpDb\Exception\MalformedURLException;

class AbuseIpDbException extends \Exception{};

class AbuseIpDb
{
    /**
     * @var string
     */
    protected $apiUrl = 'https://api.abuseipdb.com/api/v2/';

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
     * @var $spam_threshold abuse confidence score between 0 (not confident it is a spam ip) and 100 (totally confident)
     */
    protected $spam_threshold = 100;

    /**
     * @var $cache_ttl Cache duration in seconds
     */
    protected $cache_ttl = 10;

    /**
     * @return bool
     * @throws AbuseIpDbException
     */
    public function IsSpamIp($ip = null)
    {
        if(!$ip) {
            $ip = $this->getIp();
        }

        $ip = urlencode($ip);

        $result = cache()->remember('laravel-abuseipdb-' . str_slug($this->getIp()) . '-' . str_slug($this->getDays()), $this->getCacheTTL(), function () use ($ip){
            $response = $this->getResponseData('check', [
                'ipAddress' => $ip,
                'maxAgeInDays' => $this->getDays(),
            ]);

            return $response;
        });

        if ($result->abuseConfidenceScore >= $this->getSpamThreshold()) {
            event(new \nickurt\AbuseIpDb\Events\IsSpamIp($this->getIp()));

            return true;
        }

        return false;
    }

    /**
     * @param string $categories The comma separated categories to report (eg 18,22), see https://www.abuseipdb.com/categories
     * @param string $ip (optional) The ip address to report. Or set it before with $this->setIp($ip)
     * @param string $comment Related information (server logs, timestamps, etc.)
     * @return bool The updated abuse confident score
     * @throws AbuseIpDbException
     */
    public function reportIp($categories, $ip = null, $comment = '')
    {
        if(!$ip) {
            $ip = $this->getIp();
        }

        $ip = urlencode($ip);

        $result = $this->postResponseData('report', [
            'ip' => $ip,
            'categories' => $categories,
            'comment' => $comment
        ]);

        return $result->abuseConfidentScore;
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
     * @param $url
     * @return \Psr\Http\Message\ResponseInterface
     * @throws ClientException
     */
    protected function getResponseData($endpoint, $query)
    {
        $client = new Client([
            'base_uri' => $this->getApiUrl(),
            'http_errors' => false,
        ]);

        $options = [
            'query' => $query,
            'headers' => [
                'Accept' => 'application/json',
                'Key' => $this->getApiKey()
            ]
        ];

        $response = $client->request('GET', $endpoint, $options);
        $output = json_decode($response->getBody());

        // Catch errors
        if(property_exists($output, 'errors')) {
            throw new AbuseIpDbException(implode(', ', array_map(function($error){return $error->detail;}, $output->errors)));
        }

        return $output->data;
    }

    /**
     * @param $url
     * @return \Psr\Http\Message\ResponseInterface
     * @throws AbuseIpDbException
     */
    protected function postResponseData($endpoint, $query)
    {
        $client = new Client([
            'base_uri' => $this->getApiUrl(),
            'http_errors' => false,
        ]);

        $options = [
            'query' => $query,
            'headers' => [
                'Accept' => 'application/json',
                'Key' => $this->getApiKey()
            ]
        ];

        $response = $client->request('POST', $endpoint, $options);
        $output = json_decode($response->getBody());

        // Catch errors
        if(property_exists($output, 'errors')) {
            throw new AbuseIpDbException(implode(', ', array_map(function($error){return $error->detail;}, $output->errors)));
        }

        return $output->data;
    }

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
     * @return int
     */
    public function getSpamThreshold()
    {
        return $this->spam_threshold;
    }

    /**
     * @param $spam_threshold
     * @return $this
     */
    public function setSpamThreshold($spam_threshold)
    {
        $this->spam_threshold = $spam_threshold;
        return $this;
    }

    /**
     * @return int
     */
    public function getCacheTTL()
    {
        return $this->cache_ttl;
    }

    /**
     * @param $cache_ttl
     * @return $this
     */
    public function setCacheTTL($cache_ttl)
    {
        $this->cache_ttl = $cache_ttl;
        return $this;
    }
}
