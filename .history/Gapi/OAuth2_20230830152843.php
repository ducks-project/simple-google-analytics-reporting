<?php

namespace DucksProject\Component\SimpleGoogleAnalyticsReporting\Gapi;

use DucksProject\Component\SimpleGoogleAnalyticsReporting\Credentials\AssertionCredentials;
use DucksProject\Component\SimpleGoogleAnalyticsReporting\Enum\Scope;
use Google\Auth\AccessToken;
use Google\Auth\CredentialsLoader;
use Google\Auth\OAuth2 as GoogleOauth2;

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
    public const scope_url = Scope::ANALYTICS_READONLY;

    /**
     * @deprecated 1.0
     *   Use CredentialsLoader::TOKEN_CREDENTIAL_URI instead
     */
    // phpcs:ignore
    public const request_url = CredentialsLoader::TOKEN_CREDENTIAL_URI;

    /**
     * @deprecated 1.0
     *   Use OAuth2::JWT_URN instead
     */
    // phpcs:ignore
    public const grant_type = GoogleOauth2::JWT_URN;

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
    public const OAUTH2_REVOKE_URI = AccessToken::OAUTH2_REVOKE_URI;

    /**
     * @deprecated 1.0
     *   Use CredentialsLoader::TOKEN_CREDENTIAL_URI instead
     */
    // phpcs:ignore
    public const OAUTH2_TOKEN_URI = CredentialsLoader::TOKEN_CREDENTIAL_URI;

    private ?string $token = null;

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
    ): string {
        $creds = new AssertionCredentials(
            [Scope::ANALYTICS_READONLY],
            $client_email,
            $key_file,
            null,
            null,
            $delegate_email
        );

        $this->token = $creds->fetchAuthToken();

        return $this->token ?? '';
    }

    public function hasToken(): bool
    {
        return !empty($this->token);
    }

    public function getToken(): string
    {
        return $this->token ?? '';
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
