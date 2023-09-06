<?php

namespace DucksProject\Component\SimpleGoogleAnalyticsReporting;

use DucksProject\Component\SimpleGoogleAnalyticsReporting\Enum\Scope;
use Google\Auth\ApplicationDefaultCredentials;
use Google\Auth\FetchAuthTokenInterface;
use Psr\Http\Client\ClientInterface;

class Service
{
    protected ClientInterface $client;
    protected ?FetchAuthTokenInterface $credentials;

    public function __construct(
        ClientInterface $client,
        ?FetchAuthTokenInterface $credentials = null
    ) {
        $this->client = $client;
        $this->credentials = $credentials;
    }

    public function getCredentials(?array $scopes = []): FetchAuthTokenInterface
    {
        return $this->credentials
            ?: ApplicationDefaultCredentials::getCredentials($scopes ?: [Scope::ANALYTICS_RO]);
    }

    public function sendRequest($request = null)/* :Response*/
    {
        $creds = ApplicationDefaultCredentials::getCredentials();
        print_r($creds);
        die;
    }
}
