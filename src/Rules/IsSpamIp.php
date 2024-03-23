<?php

namespace nickurt\AbuseIpDb\Rules;

use Illuminate\Contracts\Validation\Rule;

class IsSpamIp implements Rule
{
    /** @var int */
    protected $days;

    /** @var string */
    protected $ip;

    /** @var int */
    protected $threshold;

    /**
     * @param  string  $ip
     * @param  int  $days
     * @param  int  $threshold
     */
    public function __construct($ip, $days = 30, $threshold = 100)
    {
        $this->ip = $ip;
        $this->days = $days;
        $this->threshold = $threshold;
    }

    /**
     * @return array|\Illuminate\Contracts\Translation\Translator|string|null
     */
    public function message()
    {
        return trans('abuseipdb::abuseipdb.it_is_currently_not_possible_to_register_with_your_specified_information_please_try_later_again');
    }

    /**
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     *
     * @throws \Exception
     */
    public function passes($attribute, $value)
    {
        /** @var \nickurt\AbuseIpDb\AbuseIpDb $sfs */
        $abuseIpDb = \AbuseIpDb::setIp($this->ip)->setDays($this->days)->setSpamThreshold($this->threshold);

        return $abuseIpDb->isSpamIp() ? false : true;
    }
}
