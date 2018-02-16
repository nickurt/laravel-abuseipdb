<?php

namespace nickurt\AbuseIpDb;

class Facade extends \Illuminate\Support\Facades\Facade
{
    /**
     * {@inheritDoc}
     */
    protected static function getFacadeAccessor()
    {
        return 'AbuseIpDb';
    }
}
