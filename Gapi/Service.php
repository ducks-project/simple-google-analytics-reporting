<?php

namespace DucksProject\Component\SimpleGoogleAnalyticsReporting\Gapi;

use DucksProject\Component\SimpleGoogleAnalyticsReporting\Gapi\Entry\Account;
use DucksProject\Component\SimpleGoogleAnalyticsReporting\Gapi\Entry\Report;
use DucksProject\Component\SimpleGoogleAnalyticsReporting\Gapi\Traits\DynamicProperties;
use DucksProject\Component\SimpleGoogleAnalyticsReporting\Gapi\Traits\Silent;

/**
 * @deprecated 1.0
 */
class Service implements Silentable
{
    use DynamicProperties;
    use Silent;

    /**
     * @deprecated 1.0
     *   Do not use
     */
    // phpcs:ignore
    public const account_data_url = 'https://www.googleapis.com/analytics/v3/management/accountSummaries';
    /**
     * @deprecated 1.0
     *   Do not use
     */
    // phpcs:ignore
    public const report_data_url = 'https://www.googleapis.com/analytics/v3/data/ga';
    /**
     * @deprecated 1.0
     *   Do not use
     */
    // phpcs:ignore
    public const interface_name = 'GAPI-2.0';
    /**
     * @deprecated 1.0
     *   Do not use
     */
    // phpcs:ignore
    public const dev_mode = false;

    private $auth = null;
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
     * @return self
     */
    public function __construct(
        $client_email,
        $key_file,
        $delegate_email = null
    ) {
        $this->auth = new OAuth2();
        $this->auth->fetchToken($client_email, $key_file, $delegate_email);
    }

    /**
     * Return the auth token string retrieved by Google.
     *
     * @return string
     */
    public function getToken()
    {
        return $this->auth->getToken();
    }

    /**
     * Return the auth token information from the Google service.
     *
     * @return array
     *
     * @deprecated 1.0
     *    Do not use
     */
    public function getTokenInfo()
    {
        return $this->auth->getTokenInfo();
    }

    /**
     * Revoke the current auth token, rendering it invalid for future requests.
     *
     * @return bool
     *
     * @deprecated 1.0
     *   Do not use
     */
    public function revokeToken(): bool
    {
        return $this->auth->revokeToken();
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
        $url = new Request(static::account_data_url);
        $response = $url->get($get_variables, $this->auth->generateAuthHeader());

        if ('2' == substr($response['code'], 0, 1)) {
            return $this->accountObjectMapper($response['body']);
        }

        if (!$this->isSilent()) {
            throw new \Exception('GAPI: Failed to request account data. Error: "' . \strip_tags($response['body'] ?? '') . '"');
        }

        return null;
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
    public function requestReportData(
        $report_id,
        $dimensions = null,
        $metrics,
        $sort_metric = null,
        $filter = null,
        $start_date = null,
        $end_date = null,
        $start_index = 1,
        $max_results = 10000
    ) {
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
        } elseif (!empty($sort_metric)) {
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

        $parameters['prettyprint'] = static::dev_mode ? 'true' : 'false';

        $url = new Request(static::report_data_url);
        $response = $url->get($parameters, $this->auth->generateAuthHeader());

        // HTTP 2xx
        if ('2' == substr($response['code'], 0, 1)) {
            return $this->reportObjectMapper($response['body']);
        }

        if (!$this->isSilent()) {
            throw new \Exception(
                'GAPI: Failed to request report data. Error: "' . $this->cleanErrorResponse($response['body'] ?? '') . '"'
            );
        }

        return null;
    }

    /**
     * Clean error message from Google API.
     *
     * @param string $error Error message HTML or JSON from Google API
     */
    private function cleanErrorResponse($error): string
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

        return strlen($filter) > 0
            ? urlencode($filter)
            : false;
    }

    /**
     * Report Account Mapper to convert the JSON to array of useful PHP objects.
     *
     * @param string $json_string
     *
     * @return array of Account objects
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
                $results[] = new Account($property);
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
                            if (!$this->isSilent()) {
                                throw new \Exception("GAPI: Unrecognized columnType '{$header['columnType']}' for columnHeader '{$header['name']}'");
                            }
                            break;
                    }
                }
                $results[] = new Report($metrics, $dimensions);
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

    protected function getObjectVars(): array
    {
        return [
            'report_root_parameters',
            'report_aggregate_metrics',
        ];
    }
}
