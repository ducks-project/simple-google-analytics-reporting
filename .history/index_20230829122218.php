<?php

ini_set('display_errors', 'On');
error_reporting(E_ALL);

use DucksProject\Component\SimpleGoogleAnalyticsReporting\Service;
use Google\Auth\CredentialsLoader;
use GuzzleHttp\Client;

require_once __DIR__ . '/vendor/autoload.php';

$jsonKey = ['type' => 'service_account'];
$creds = CredentialsLoader::makeCredentials($scopes ?? [], $jsonKey);

$client = new Client();
$service = new Service($client);
$service->sendRequest();
