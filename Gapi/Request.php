<?php

namespace Ducks\Component\SimpleGoogleAnalyticsReporting\Gapi;

class Request
{
    // 'auto': autodetect, 'curl' or 'fopen'
    // phpcs:ignore
    public const http_interface = 'auto';
    // phpcs:ignore
    public const interface_name = Service::interface_name;

    private ?string $url = null;

    private array $gets = [];
    private array $posts = [];
    private array $headers = [];

    private function parseVariables($variables): string
    {
        return \is_array($variables)
            ? '?' . str_replace(
                '&amp;',
                '&',
                urldecode(http_build_query(\array_filter($variables), '', '&'))
            )
            : '';
    }

    public function __construct(string $url)
    {
        $this->url = $url;
    }

    public function addGets(array $variables): self
    {
        $this->gets = array_replace(
            $this->gets,
            $variables
        );

        return $this;
    }

    public function hasGets(): bool
    {
        return !empty($this->gets);
    }

    public function addPosts(array $variables): self
    {
        $this->posts = array_replace(
            $this->posts,
            $variables
        );

        return $this;
    }

    public function hasPosts(): bool
    {
        return !empty($this->posts);
    }

    public function addHeaders(array $variables): self
    {
        $this->headers = array_replace(
            $this->headers,
            $variables
        );

        return $this;
    }

    public function hasHeaders(): bool
    {
        return !empty($this->headers);
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
        $gets = \array_replace($this->gets, $get_variables ?? []);
        return $this->url . $this->parseVariables($gets);
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

        $this->addGets($get_variables ?? []);
        $this->addPosts($post_variables ?? []);
        $this->addHeaders($headers ?? []);

        return $this->$method();
    }

    /**
     * HTTP request using PHP CURL functions
     * Requires curl library installed and configured for PHP.
     */
    protected function curlRequest()
    {
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $this->getUrl());
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // CURL doesn't like google's cert

        if ($this->hasPosts()) {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($this->posts, '', '&'));
        }

        if ($this->hasHeaders()) {
            $string_headers = [];
            foreach ($this->headers as $key => $value) {
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
    protected function fopenRequest()
    {
        $http_options = ['method' => 'GET', 'timeout' => 3];

        $string_headers = '';
        if ($this->hasHeaders()) {
            foreach ($this->headers as $key => $value) {
                $string_headers .= "$key: $value\r\n";
            }
        }

        if ($this->hasPosts()) {
            $post_variables = str_replace('&amp;', '&', urldecode(http_build_query($this->posts, '', '&')));
            $http_options['method'] = 'POST';
            $string_headers = "Content-type: application/x-www-form-urlencoded\r\n"
                . "Content-Length: " . strlen($post_variables) . "\r\n" . $string_headers;
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
