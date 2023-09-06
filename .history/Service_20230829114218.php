<?php

namespace DucksProject\Component\SimpleGoogleAnalyticsReporting;

use Google\Auth\ApplicationDefaultCredentials;
use Psr\Http\Client\ClientInterface;

class Service
{
    protected ClientInterface $client;

    public function __construct(ClientInterface $client)
    {
        $this->client = $client;
    }

    public function sendRequest($request = null)/* :Response*/
    {
        $creds = ApplicationDefaultCredentials::getCredentials();
        print_r($creds);
        die;
    }
}
