<?php

ini_set('display_errors', 'On');
error_reporting(E_ALL);

use DucksProject\Component\SimpleGoogleAnalyticsReporting\Credentials\AssertionCredentials;
use DucksProject\Component\SimpleGoogleAnalyticsReporting\Service;
use Google\Auth\CredentialsLoader;
use GuzzleHttp\Client;

require_once __DIR__ . '/vendor/autoload.php';

$creds = new AssertionCredentials(
    '',
    'service-account-2-341@api-project-989413626351.iam.gserviceaccount.com',
    __DIR__ . '/.creds/p12/api-project-989413626351-6371026180c5.p12'
);
$oauth = $creds->fetchAuthToken();
dd($oauth);

$jsonKey = ['type' => 'service_account'];
$creds = CredentialsLoader::makeCredentials($scopes ?? [], $jsonKey);

$client = new Client();
$service = new Service($client);
$service->sendRequest();
