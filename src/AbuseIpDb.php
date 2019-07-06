<?php

namespace nickurt\AbuseIpDb;

use \GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use \nickurt\AbuseIpDb\Exception\MalformedURLException;
use \nickurt\AbuseIpDb\Exception\AbuseIpDbException;

class AbuseIpDb
{
    /** @var string */
    protected $apiKey;

    /** @var string */
    protected $apiUrl = 'https://api.abuseipdb.com/api/v2';

    /** @var int */
    protected $cache_ttl = 10;

    /** @var \GuzzleHttp\Client */
    protected $client;

    /** @var int */
    protected $days = 30;

    /** @var string */
    protected $ip;

    /** @var int */
    protected $spam_threshold = 100;

    /**
     * @param null|string $ip
     * @return bool
     * @throws \Exception
     */
    public function IsSpamIp($ip = null)
    {
        $ip = urlencode($ip ?? $this->getIp());

        $result = cache()->remember('laravel-abuseipdb-' . str_slug($ip) . '-' . str_slug($this->getDays()), $this->getCacheTTL(), function () use ($ip) {
            return $this->getResponseData('check', [
                'ipAddress' => $ip,
                'maxAgeInDays' => $this->getDays(),
            ]);
        });

        if ($result->abuseConfidenceScore >= $this->getSpamThreshold()) {
            event(new \nickurt\AbuseIpDb\Events\IsSpamIp($this->getIp()));

            return true;
        }

        return false;
    }

    /**
     * @return string
     */
    public function getIp()
    {
        return $this->ip;
    }

    /**
     * @param string $ip
     * @return $this
     */
    public function setIp($ip)
    {
        $this->ip = $ip;

        return $this;
    }

    /**
     * @return int
     */
    public function getDays()
    {
        return $this->days;
    }

    /**
     * @param int $days
     * @return $this
     */
    public function setDays($days)
    {
        $this->days = $days;

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
     * @param int $cache_ttl
     * @return $this
     */
    public function setCacheTTL($cache_ttl)
    {
        $this->cache_ttl = $cache_ttl;

        return $this;
    }

    /**
     * @param string $endpoint
     * @param array $query
     * @return object
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    protected function getResponseData($endpoint, $query)
    {
        try {
            $response = $this->getClient()->request('GET', $this->getApiUrl() . '/' . $endpoint, [
                'query' => $query,
                'headers' => [
                    'Accept' => 'application/json',
                    'Key' => $this->getApiKey()
                ]
            ]);
        } catch (\Exception $e) {
            $response = $e->getResponse();
        }

        $output = json_decode($response->getBody());

        if (property_exists($output, 'errors')) {
            throw new AbuseIpDbException(implode(', ', array_map(function ($error) {
                return $error->detail;
            }, $output->errors)));
        }

        return $output->data;
    }

    /**
     * @return Client
     */
    public function getClient()
    {
        if (!isset($this->client)) {
            $this->client = new Client();

            return $this->client;
        }

        return $this->client;
    }

    /**
     * @param $client
     * @return $this
     */
    public function setClient($client)
    {
        $this->client = $client;

        return $this;
    }

    /**
     * @return string
     */
    public function getApiUrl()
    {
        return $this->apiUrl;
    }

    /**
     * @param string $apiUrl
     * @return $this
     * @throws MalformedURLException
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
     * @return string
     */
    public function getApiKey()
    {
        return $this->apiKey;
    }

    /**
     * @param string $apiKey
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
     * @param int $spamThreshold
     * @return $this
     */
    public function setSpamThreshold($spamThreshold)
    {
        $this->spam_threshold = $spamThreshold;

        return $this;
    }

    /**
     * @param string $categories
     * @param null|string $ip
     * @param string $comment
     * @return object
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function reportIp($categories, $ip = null, $comment = '')
    {
        $ip = urlencode($ip ?? $this->getIp());

        $result = $this->postResponseData('report', [
            'ip' => $ip,
            'categories' => $categories,
            'comment' => $comment
        ]);

        return $result->abuseConfidenceScore;
    }

    /**
     * @param string $endpoint
     * @param array $query
     * @return object
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    protected function postResponseData($endpoint, $query)
    {
        try {
            $response = $this->getClient()->request('POST', $this->getApiUrl() . '/' . $endpoint, [
                'query' => $query,
                'headers' => [
                    'Accept' => 'application/json',
                    'Key' => $this->getApiKey()
                ]
            ]);
        } catch (\Exception $e) {
            $response = $e->getResponse();
        }

        $output = json_decode($response->getBody());

        if (property_exists($output, 'errors')) {
            throw new AbuseIpDbException(implode(', ', array_map(function ($error) {
                return $error->detail;
            }, $output->errors)));
        }

        return $output->data;
    }
}
