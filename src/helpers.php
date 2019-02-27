<?php

use nickurt\AbuseIpDb\AbuseIpDb;

if (!function_exists('abuseipdb')) {
    function abuseipdb()
    {
        return app(AbuseIpDb::class);
    }
}