<?php

ini_set('display_errors', 'On');
error_reporting(E_ALL);

use DucksProject\Component\SimpleGoogleAnalyticsReporting\Service;
use GuzzleHttp\Client;

require_once __DIR__ . '/vendor/autoload.php';

$client = new Client();
$service = new Service($client);
$service->sendRequest();
