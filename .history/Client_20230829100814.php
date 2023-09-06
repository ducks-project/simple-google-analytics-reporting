<?php

namespace DucksProject\Component\SimpleGoogleAnalyticsReporting;

use Psr\Http\Client\ClientInterface;

class Service
{
    protected ClientInterface $client;

    public function __construct(ClientInterface $client)
    {
        $this->client = $client;
    }

    public function sendRequest($request)/* :Response*/
    {

    }

}
