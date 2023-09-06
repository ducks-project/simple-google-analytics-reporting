<?php

namespace DucksProject\Component\SimpleGoogleAnalyticsReporting\Gapi;

/**
 * @deprecated 1.0
 *    Use AssertionCredentials instead
 */
class OAuth2
{
    /**
     * @deprecated 1.0
     *   Use Scope::ANALYTICS_READONLY instead
     */
    // phpcs:ignore
    public const scope_url = 'https://www.googleapis.com/auth/analytics.readonly';

    /**
     * @deprecated 1.0
     *   Use CredentialsLoader::TOKEN_CREDENTIAL_URI instead
     */
    // phpcs:ignore
    public const request_url = 'https://www.googleapis.com/oauth2/v3/token';

    /**
     * @deprecated 1.0
     *   Use OAuth2::JWT_URN instead
     */
    // phpcs:ignore
    public const grant_type = 'urn:ietf:params:oauth:grant-type:jwt-bearer';

    /**
     * @deprecated 1.0
     *   Do not use
     */
    // phpcs:ignore
    public const header_alg = 'RS256';

    /**
     * @deprecated 1.0
     *   Do not use
     */
    // phpcs:ignore
    public const header_typ = 'JWT';

    /**
     * @deprecated 1.0
     *   Use AccessToken::OAUTH2_REVOKE_URI instead
     */
    // phpcs:ignore
    public const OAUTH2_REVOKE_URI = 'https://oauth2.googleapis.com/revoke';

    /**
     * @deprecated 1.0
     *   Use CredentialsLoader::TOKEN_CREDENTIAL_URI instead
     */
    // phpcs:ignore
    public const OAUTH2_TOKEN_URI = 'https://oauth2.googleapis.com/token';

    private ?string $token = null;

    private function base64URLEncode($data)
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    private function base64URLDecode($data)
    {
        return base64_decode(str_pad(strtr($data, '-_', '+/'), strlen($data) % 4, '=', STR_PAD_RIGHT));
    }

    /**
     * Authenticate Google Account with OAuth2.
     *
     * @param string $client_email
     * @param string $key_file
     * @param string $delegate_email
     *
     * @return string Authentication token
     *
     * @deprecated 1.1
     *    Use AssertionCredentials instead
     */
    public function fetchToken(
        $client_email,
        #[\SensitiveParameter]
        $key_file,
        $delegate_email = null
    ) {
        $header = [
            "alg" => self::header_alg,
            "typ" => self::header_typ,
        ];

        $claimset = [
            "iss" => $client_email,
            "scope" => self::scope_url,
            "aud" => self::request_url,
            "exp" => time() + (60 * 60),
            "iat" => time(),
        ];

        if (!empty($delegate_email)) {
            $claimset["sub"] = $delegate_email;
        }

        $data = $this->base64URLEncode(json_encode($header)) . '.' . $this->base64URLEncode(json_encode($claimset));

        if (!file_exists($key_file)) {
            if (!file_exists(__DIR__ . DIRECTORY_SEPARATOR . $key_file)) {
                // throw new Exception('GAPI: Failed load key file "' . $key_file . '". File could not be found.');
            } else {
                $key_file = __DIR__ . DIRECTORY_SEPARATOR . $key_file;
            }
        }

        $key_data = file_get_contents($key_file);

        if (empty($key_data)) {
            // throw new Exception('GAPI: Failed load key file "' . $key_file . '". File could not be opened or is empty.');
        }

        openssl_pkcs12_read($key_data, $certs, 'notasecret');

        if (!isset($certs['pkey'])) {
            // throw new Exception('GAPI: Failed load key file "' . $key_file . '". Unable to load pkcs12 check if correct p12 format.');
        }

        openssl_sign($data, $signature, openssl_pkey_get_private($certs['pkey']), "sha256");

        $post_variables = [
            'grant_type' => self::grant_type,
            'assertion' => $data . '.' . $this->base64URLEncode($signature),
        ];

        $url = new Request(self::request_url);
        $response = $url->post(null, $post_variables);
        $auth_token = json_decode($response['body'], true);

        if ('2' != substr($response['code'], 0, 1) || !is_array($auth_token) || empty($auth_token['access_token'])) {
            // throw new Exception('GAPI: Failed to authenticate user. Error: "' . strip_tags($response['body']) . '"');
        }

        $this->token = $auth_token['access_token'];

        return $this->token;
    }

    public function hasToken(): bool
    {
        return !empty($this->token);
    }

    /**
     * Return the auth token string retrieved from Google.
     *
     * @return string
     */
    public function getToken(): string
    {
        return $this->token ?? '';
    }

    /**
     *
     * @return array
     *
     * @deprecated 1.0
     *   Unused
     */
    public function getTokenInfo(): array
    {
        return [];
    }

    /**
     *
     * @deprecated 1.0
     *   Unused
     */
    public function revokeToken(): bool
    {
        return false;
    }

    /**
     * Generate authorization token header for all requests.
     *
     * @param string $token
     *
     * @return array
     */
    public function generateAuthHeader(string $token = null)
    {
        return [
            'Authorization' => 'Bearer ' . ($token ?? $this->getToken()),
        ];
    }
}
