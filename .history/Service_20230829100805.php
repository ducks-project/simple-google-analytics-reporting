<?php

namespace DucksProject\Component\SimpleGoogleAnalyticsReporting;

class gapi
{
    public const account_data_url = 'https://www.googleapis.com/analytics/v3/management/accountSummaries';
    public const report_data_url = 'https://www.googleapis.com/analytics/v3/data/ga';
    public const interface_name = 'GAPI-2.0';
    public const dev_mode = false;

    private $auth_method = null;
    private $account_entries = [];
    private $report_aggregate_metrics = [];
    private $report_root_parameters = [];
    private $results = [];

    /**
     * Constructor function for new gapi instances.
     *
     * @param string $client_email Email of OAuth2 service account (XXXXX@developer.gserviceaccount.com)
     * @param string $key_file Location/filename of .p12 key file
     * @param string $delegate_email Optional email of account to impersonate
     *
     * @return gapi
     */
    public function __construct($client_email, $key_file, $delegate_email = null)
    {
        if (version_compare(PHP_VERSION, '5.3.0') < 0) {
            throw new Exception('GAPI: PHP version ' . PHP_VERSION . ' is below minimum required 5.3.0.');
        }
        $this->auth_method = new gapiOAuth2();
        $this->auth_method->fetchToken($client_email, $key_file, $delegate_email);
    }

    /**
     * Return the auth token string retrieved by Google.
     *
     * @return string
     */
    public function getToken()
    {
        return $this->auth_method->getToken();
    }

    /**
     * Return the auth token information from the Google service.
     *
     * @return array
     */
    public function getTokenInfo()
    {
        return $this->auth_method->getTokenInfo();
    }

    /**
     * Revoke the current auth token, rendering it invalid for future requests.
     *
     * @return bool
     */
    public function revokeToken()
    {
        return $this->auth_method->revokeToken();
    }

    /**
     * Request account data from Google Analytics.
     *
     * @param int $start_index OPTIONAL: Start index of results
     * @param int $max_results OPTIONAL: Max results returned
     */
    public function requestAccountData($start_index = 1, $max_results = 1000)
    {
        $get_variables = [
            'start-index' => $start_index,
            'max-results' => $max_results,
        ];
        $url = new gapiRequest(gapi::account_data_url);
        $response = $url->get($get_variables, $this->auth_method->generateAuthHeader());

        if ('2' == substr($response['code'], 0, 1)) {
            return $this->accountObjectMapper($response['body']);
        } else {
            throw new Exception('GAPI: Failed to request account data. Error: "' . strip_tags($response['body']) . '"');
        }
    }

    /**
     * Request report data from Google Analytics.
     *
     * $report_id is the Google report ID for the selected account
     *
     * $parameters should be in key => value format
     *
     * @param string $report_id
     * @param array $dimensions Google Analytics dimensions e.g. array('browser')
     * @param array $metrics Google Analytics metrics e.g. array('pageviews')
     * @param array $sort_metric OPTIONAL: Dimension or dimensions to sort by e.g.('-visits')
     * @param string $filter OPTIONAL: Filter logic for filtering results
     * @param string $start_date OPTIONAL: Start of reporting period
     * @param string $end_date OPTIONAL: End of reporting period
     * @param int $start_index OPTIONAL: Start index of results
     * @param int $max_results OPTIONAL: Max results returned
     */
    public function requestReportData($report_id, $dimensions = null, $metrics, $sort_metric = null, $filter = null, $start_date = null, $end_date = null, $start_index = 1, $max_results = 10000)
    {
        $parameters = ['ids' => 'ga:' . $report_id];

        if (is_array($dimensions)) {
            $dimensions_string = '';
            foreach ($dimensions as $dimesion) {
                $dimensions_string .= ',ga:' . $dimesion;
            }
            $parameters['dimensions'] = substr($dimensions_string, 1);
        } elseif (null !== $dimensions) {
            $parameters['dimensions'] = 'ga:' . $dimensions;
        }

        if (is_array($metrics)) {
            $metrics_string = '';
            foreach ($metrics as $metric) {
                $metrics_string .= ',ga:' . $metric;
            }
            $parameters['metrics'] = substr($metrics_string, 1);
        } else {
            $parameters['metrics'] = 'ga:' . $metrics;
        }

        if (null == $sort_metric && isset($parameters['metrics'])) {
            $parameters['sort'] = $parameters['metrics'];
        } elseif (is_array($sort_metric)) {
            $sort_metric_string = '';

            foreach ($sort_metric as $sort_metric_value) {
                // Reverse sort - Thanks Nick Sullivan
                if ("-" == substr($sort_metric_value, 0, 1)) {
                    $sort_metric_string .= ',-ga:' . substr($sort_metric_value, 1); // Descending
                } else {
                    $sort_metric_string .= ',ga:' . $sort_metric_value; // Ascending
                }
            }

            $parameters['sort'] = substr($sort_metric_string, 1);
        } else {
            if ("-" == substr($sort_metric, 0, 1)) {
                $parameters['sort'] = '-ga:' . substr($sort_metric, 1);
            } else {
                $parameters['sort'] = 'ga:' . $sort_metric;
            }
        }

        if (null != $filter) {
            $filter = $this->processFilter($filter);
            if (false !== $filter) {
                $parameters['filters'] = $filter;
            }
        }

        if (null == $start_date) {
            // Use the day that Google Analytics was released (1 Jan 2005).
            $start_date = '2005-01-01';
        } elseif (is_int($start_date)) {
            // Perhaps we are receiving a Unix timestamp.
            $start_date = date('Y-m-d', $start_date);
        }

        $parameters['start-date'] = $start_date;

        if (null == $end_date) {
            $end_date = date('Y-m-d');
        } elseif (is_int($end_date)) {
            // Perhaps we are receiving a Unix timestamp.
            $end_date = date('Y-m-d', $end_date);
        }

        $parameters['end-date'] = $end_date;

        $parameters['start-index'] = $start_index;
        $parameters['max-results'] = $max_results;

        $parameters['prettyprint'] = gapi::dev_mode ? 'true' : 'false';

        $url = new gapiRequest(gapi::report_data_url);
        $response = $url->get($parameters, $this->auth_method->generateAuthHeader());

        // HTTP 2xx
        if ('2' == substr($response['code'], 0, 1)) {
            return $this->reportObjectMapper($response['body']);
        } else {
            throw new Exception('GAPI: Failed to request report data. Error: "' . $this->cleanErrorResponse($response['body']) . '"');
        }
    }

    /**
     * Clean error message from Google API.
     *
     * @param string $error Error message HTML or JSON from Google API
     */
    private function cleanErrorResponse($error)
    {
        if (false !== strpos($error, '<html')) {
            $error = preg_replace('/<(style|title|script)[^>]*>[^<]*<\/(style|title|script)>/i', '', $error);
            return trim(preg_replace('/\s+/', ' ', strip_tags($error)));
        } else {
            $json = json_decode($error);
            return isset($json->error->message) ? strval($json->error->message) : $error;
        }
    }

    /**
     * Process filter string, clean parameters and convert to Google Analytics
     * compatible format.
     *
     * @param string $filter
     *
     * @return string Compatible filter string
     */
    protected function processFilter($filter)
    {
        $valid_operators = '(!~|=~|==|!=|>|<|>=|<=|=@|!@)';

        $filter = preg_replace('/\s\s+/', ' ', trim($filter)); // Clean duplicate whitespace
        $filter = str_replace([',', ';'], ['\,', '\;'], $filter); // Escape Google Analytics reserved characters
        $filter = preg_replace('/(&&\s*|\|\|\s*|^)([a-z0-9]+)(\s*' . $valid_operators . ')/i', '$1ga:$2$3', $filter); // Prefix ga: to metrics and dimensions
        $filter = preg_replace('/[\'\"]/i', '', $filter); // Clear invalid quote characters
        $filter = preg_replace(['/\s*&&\s*/', '/\s*\|\|\s*/', '/\s*' . $valid_operators . '\s*/'], [';', ',', '$1'], $filter); // Clean up operators

        if (strlen($filter) > 0) {
            return urlencode($filter);
        } else {
            return false;
        }
    }

    /**
     * Report Account Mapper to convert the JSON to array of useful PHP objects.
     *
     * @param string $json_string
     *
     * @return array of gapiAccountEntry objects
     */
    protected function accountObjectMapper($json_string)
    {
        $json = json_decode($json_string, true);
        $results = [];

        foreach ($json['items'] as $item) {
            foreach ($item['webProperties'] as $property) {
                if (isset($property['profiles'][0]['id'])) {
                    $property['ProfileId'] = $property['profiles'][0]['id'];
                }
                $results[] = new gapiAccountEntry($property);
            }
        }

        $this->account_entries = $results;

        return $results;
    }

    /**
     * Report Object Mapper to convert the JSON to array of useful PHP objects.
     *
     * @param string $json_string
     *
     * @return array of gapiReportEntry objects
     */
    protected function reportObjectMapper($json_string)
    {
        $json = json_decode($json_string, true);

        $this->results = null;
        $results = [];

        $report_aggregate_metrics = [];

        // Load root parameters

        // Start with elements from the root level of the JSON that aren't themselves arrays.
        $report_root_parameters = array_filter($json, fn ($var) => !is_array($var));

        // Get the items from the 'query' object, and rename them slightly.
        foreach ($json['query'] as $index => $value) {
            $new_index = lcfirst(str_replace(' ', '', ucwords(str_replace('-', ' ', $index))));
            $report_root_parameters[$new_index] = $value;
        }

        // Now merge in the profileInfo, as this is also mostly useful.
        array_merge($report_root_parameters, $json['profileInfo']);

        // Load result aggregate metrics

        foreach ($json['totalsForAllResults'] as $index => $metric_value) {
            // Check for float, or value with scientific notation
            if (preg_match('/^(\d+\.\d+)|(\d+E\d+)|(\d+.\d+E\d+)$/', $metric_value)) {
                $report_aggregate_metrics[str_replace('ga:', '', $index)] = floatval($metric_value);
            } else {
                $report_aggregate_metrics[str_replace('ga:', '', $index)] = intval($metric_value);
            }
        }

        // Load result entries
        if (isset($json['rows'])) {
            foreach ($json['rows'] as $row) {
                $metrics = [];
                $dimensions = [];
                foreach ($json['columnHeaders'] as $index => $header) {
                    switch ($header['columnType']) {
                        case 'METRIC':
                            $metric_value = $row[$index];

                            // Check for float, or value with scientific notation
                            if (preg_match('/^(\d+\.\d+)|(\d+E\d+)|(\d+.\d+E\d+)$/', $metric_value)) {
                                $metrics[str_replace('ga:', '', $header['name'])] = floatval($metric_value);
                            } else {
                                $metrics[str_replace('ga:', '', $header['name'])] = intval($metric_value);
                            }
                            break;
                        case 'DIMENSION':
                            $dimensions[str_replace('ga:', '', $header['name'])] = strval($row[$index]);
                            break;
                        default:
                            throw new Exception("GAPI: Unrecognized columnType '{$header['columnType']}' for columnHeader '{$header['name']}'");
                    }
                }
                $results[] = new gapiReportEntry($metrics, $dimensions);
            }
        }

        $this->report_root_parameters = $report_root_parameters;
        $this->report_aggregate_metrics = $report_aggregate_metrics;
        $this->results = $results;

        return $results;
    }

    /**
     * Get current analytics results.
     *
     * @return array
     */
    public function getResults()
    {
        return is_array($this->results) ? $this->results : false;
    }

    /**
     * Get current account data.
     *
     * @return array
     */
    public function getAccounts()
    {
        return is_array($this->account_entries) ? $this->account_entries : false;
    }

    /**
     * Get an array of the metrics and the matching
     * aggregate values for the current result.
     *
     * @return array
     */
    public function getMetrics()
    {
        return $this->report_aggregate_metrics;
    }

    /**
     * Call method to find a matching root parameter or
     * aggregate metric to return.
     *
     * @param $name String name of function called
     *
     * @return string
     *
     * @throws Exception if not a valid parameter or aggregate
     *                   metric, or not a 'get' function
     */
    public function __call($name, $parameters)
    {
        if (!preg_match('/^get/', $name)) {
            throw new Exception('No such function "' . $name . '"');
        }

        $name = preg_replace('/^get/', '', $name);

        $parameter_key = gapi::ArrayKeyExists($name, $this->report_root_parameters);

        if ($parameter_key) {
            return $this->report_root_parameters[$parameter_key];
        }

        $aggregate_metric_key = gapi::ArrayKeyExists($name, $this->report_aggregate_metrics);

        if ($aggregate_metric_key) {
            return $this->report_aggregate_metrics[$aggregate_metric_key];
        }

        throw new Exception('No valid root parameter or aggregate metric called "' . $name . '"');
    }

    /**
     * Case insensitive array_key_exists function, also returns
     * matching key.
     *
     * @param string $key
     * @param array $search
     *
     * @return string Matching array key
     */
    public static function ArrayKeyExists($key, $search)
    {
        if (array_key_exists($key, $search)) {
            return $key;
        }
        if (!(is_string($key) && is_array($search))) {
            return false;
        }
        $key = strtolower($key);
        foreach ($search as $k => $v) {
            if (strtolower($k) == $key) {
                return $k;
            }
        }
        return false;
    }
}