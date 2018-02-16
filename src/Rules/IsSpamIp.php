<?php

namespace nickurt\AbuseIpDb\Rules;

use Illuminate\Contracts\Validation\Rule;

class IsSpamIp implements Rule
{
    /**
     * @var
     */
    protected $ip;

    /**
     * @var
     */
    protected $days;

    /**
     * Create a new rule instance.
     *
     * @param $ip
     * @param $days
     *
     * @return void
     */
    public function __construct($ip, $days = 30)
    {
        $this->ip = $ip;
        $this->days = $days;
    }

    /**
     * Determine if the validation rule passes.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function passes($attribute, $value)
    {
        $sfs = (new \nickurt\AbuseIpDb\AbuseIpDb())
            ->setIp($this->ip)
            ->setDays($this->days);

        return $sfs->isSpamIp() ? false : true;
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return trans('abuseipdb::abuseipdb.it_is_currently_not_possible_to_register_with_your_specified_information_please_try_later_again');
    }
}
