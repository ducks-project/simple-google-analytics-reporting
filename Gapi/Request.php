<?php

namespace DucksProject\Component\SimpleGoogleAnalyticsReporting\Gapi;

class Request
{
    // 'auto': autodetect, 'curl' or 'fopen'
    // phpcs:ignore
    public const http_interface = 'auto';
    // phpcs:ignore
    public const interface_name = Service::interface_name;

    private ?string $url = null;

    private function parseVariables($variables): string
    {
        return \is_array($variables)
            ? '?' . str_replace('&amp;', '&', urldecode(http_build_query($variables, '', '&')))
            : '';
    }

    public function __construct(string $url)
    {
        $this->url = $url;
    }

    /**
     * Return the URL to be requested, optionally adding GET variables.
     *
     * @param array $get_variables
     *
     * @return string
     */
    public function getUrl($get_variables = null): string
    {
        return $this->url . $this->parseVariables($get_variables);
    }

    /**
     * Perform http POST request.
     *
     * @param array $get_variables
     * @param array $post_variables
     * @param array $headers
     */
    public function post($get_variables = null, $post_variables = null, $headers = null)
    {
        return $this->request($get_variables, $post_variables, $headers);
    }

    /**
     * Perform http GET request.
     *
     * @param array $get_variables
     * @param array $headers
     */
    public function get($get_variables = null, $headers = null)
    {
        return $this->request($get_variables, null, $headers);
    }

    /**
     * Perform http request.
     *
     * @param array $get_variables
     * @param array $post_variables
     * @param array $headers
     */
    public function request($get_variables = null, $post_variables = null, $headers = null)
    {
        $method = ('fopen' !== self::http_interface && !\function_exists('curl_exec'))
            ? 'fopenRequest'
            : 'curlRequest';

        return $this->$method($get_variables, $post_variables, $headers);
    }

    /**
     * HTTP request using PHP CURL functions
     * Requires curl library installed and configured for PHP.
     *
     * @param array $get_variables
     * @param array $post_variables
     * @param array $headers
     */
    protected function curlRequest($get_variables = null, $post_variables = null, $headers = null)
    {
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $this->getUrl($get_variables));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // CURL doesn't like google's cert

        if (is_array($post_variables)) {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post_variables, '', '&'));
        }

        if (is_array($headers)) {
            $string_headers = [];
            foreach ($headers as $key => $value) {
                $string_headers[] = "$key: $value";
            }
            curl_setopt($ch, CURLOPT_HTTPHEADER, $string_headers);
        }

        $response = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        curl_close($ch);

        return [
            'body' => $response,
            'code' => $code
        ];
    }

    /**
     * HTTP request using native PHP fopen function
     * Requires PHP openSSL.
     *
     * @param array $get_variables
     * @param array $post_variables
     * @param array $headers
     */
    protected function fopenRequest($get_variables = null, $post_variables = null, $headers = null)
    {
        $http_options = ['method' => 'GET', 'timeout' => 3];

        $string_headers = '';
        if (is_array($headers)) {
            foreach ($headers as $key => $value) {
                $string_headers .= "$key: $value\r\n";
            }
        }

        if (is_array($post_variables)) {
            $post_variables = str_replace('&amp;', '&', urldecode(http_build_query($post_variables, '', '&')));
            $http_options['method'] = 'POST';
            $string_headers = "Content-type: application/x-www-form-urlencoded\r\n" . "Content-Length: " . strlen($post_variables) . "\r\n" . $string_headers;
            $http_options['header'] = $string_headers;
            $http_options['content'] = $post_variables;
        } else {
            $post_variables = '';
            $http_options['header'] = $string_headers;
        }

        $context = stream_context_create(['http' => $http_options]);
        $response = @file_get_contents($this->getUrl(), false, $context);

        return [
            'body' => false !== $response ? $response : 'Request failed, consider using php5-curl module for more information.',
            'code' => false !== $response ? '200' : '400'
        ];
    }
}
