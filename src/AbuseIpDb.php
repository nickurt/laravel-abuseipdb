<?php

namespace nickurt\AbuseIpDb;

use Exception;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use nickurt\AbuseIpDb\Events\IsSpamIp;
use nickurt\AbuseIpDb\Exception\AbuseIpDbException;
use nickurt\AbuseIpDb\Exception\MalformedURLException;

class AbuseIpDb
{
    /** @var string */
    protected $apiKey;

    /** @var string */
    protected $apiUrl = 'https://api.abuseipdb.com/api/v2';

    /** @var int */
    protected $cache_ttl = 10;

    /** @var int */
    protected $days = 30;

    /** @var string */
    protected $ip;

    /** @var int */
    protected $spam_threshold = 100;

    /**
     * @param  null|string  $ip
     * @return bool
     *
     * @throws Exception
     */
    public function IsSpamIp($ip = null)
    {
        $this->setIp($ip ?? $this->getIp());

        $result = cache()->remember('laravel-abuseipdb-'.Str::slug($this->getIp()).'-'.Str::slug($this->getDays()), $this->getCacheTTL(), function () {
            return $this->getResponseData('check', [
                'ipAddress' => $this->getIp(),
                'maxAgeInDays' => $this->getDays(),
            ]);
        });

        if ($result['abuseConfidenceScore'] >= $this->getSpamThreshold()) {
            event(new IsSpamIp($this->getIp(), $result['abuseConfidenceScore']));

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
     * @param  string  $ip
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
     * @param  int  $days
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
     * @param  int  $cache_ttl
     * @return $this
     */
    public function setCacheTTL($cache_ttl)
    {
        $this->cache_ttl = $cache_ttl;

        return $this;
    }

    /**
     * @return mixed
     */
    protected function getResponseData($endpoint, $query)
    {
        try {
            $output = Http::withHeaders([
                'Accept' => 'application/json',
                'Key' => $this->getApiKey(),
            ])->withQueryParameters($query)->get($this->getApiUrl().'/'.$endpoint)->json();
        } catch (Exception $e) {
            $output = $e->getMessage();
        }

        if (is_null($output)) {
            throw new AbuseIpDbException('abuseipdb returned an invalid json response: "'.$output.'".');
        }

        if (isset($output['errors'])) {
            throw new AbuseIpDbException(implode(', ', array_map(function ($error) {
                return $error['detail'];
            }, $output['errors'])));
        }

        return $output['data'];
    }

    /**
     * @return string
     */
    public function getApiUrl()
    {
        return $this->apiUrl;
    }

    /**
     * @param  string  $apiUrl
     * @return $this
     *
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
     * @param  string  $apiKey
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
     * @param  int  $spamThreshold
     * @return $this
     */
    public function setSpamThreshold($spamThreshold)
    {
        $this->spam_threshold = $spamThreshold;

        return $this;
    }

    /**
     * @param  string  $categories
     * @param  null|string  $ip
     * @param  string  $comment
     * @return object
     *
     * @throws GuzzleException
     */
    public function reportIp($categories, $ip = null, $comment = '')
    {
        $this->setIp($ip ?? $this->getIp());

        $result = $this->postResponseData('report', [
            'ip' => $this->getIp(),
            'categories' => $categories,
            'comment' => $comment,
        ]);

        return $result['abuseConfidenceScore'];
    }

    /**
     * @return mixed
     */
    protected function postResponseData($endpoint, $query)
    {
        try {
            $output = Http::withHeaders([
                'Accept' => 'application/json',
                'Key' => $this->getApiKey(),
            ])->withQueryParameters($query)->post($this->getApiUrl().'/'.$endpoint)->json();
        } catch (Exception $e) {
            $output = $e->getMessage();
        }

        if (is_null($output)) {
            throw new AbuseIpDbException('abuseipdb returned an invalid json response: "'.$output.'".');
        }

        if (isset($output['errors'])) {
            throw new AbuseIpDbException(implode(', ', array_map(function ($error) {
                return $error['detail'];
            }, $output['errors'])));
        }

        return $output['data'];
    }
}
