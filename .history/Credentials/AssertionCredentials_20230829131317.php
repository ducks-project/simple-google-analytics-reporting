<?php

namespace DucksProject\Component\SimpleGoogleAnalyticsReporting;

use Firebase\JWT\JWT;
use Google\Auth\AccessToken;
use Google\Auth\CredentialsLoader;
use Google\Auth\FetchAuthTokenInterface;
use Google\Auth\OAuth2;

/**
 *
 * @link https://github.com/googleapis/google-api-php-client/blob/v1.1.9/src/Google/Auth/AssertionCredentials.php
 * @link https://github.com/Nyholm/google-api-php-client/blob/master/src/GoogleApi/Auth/AssertionCredentials.php
 */
class AssertionCredentials implements FetchAuthTokenInterface
{
    const MAX_TOKEN_LIFETIME_SECS = 3600;

    private AccessToken $accessToken;

    public $serviceAccountName;
    public string $scopes;
    public $privateKey;
    public $privateKeyPassword;
    public $assertionType;
    public $sub;


    /**
     * @deprecated
     * @link http://tools.ietf.org/html/draft-ietf-oauth-json-web-token-06
     */
    public $prn;

    /**
     * @param $serviceAccountName
     * @param string|array $scopes array List of scopes
     * @param $privateKey
     * @param string $privateKeyPassword
     * @param string $assertionType
     * @param bool|string $sub The email address of the user for which the
     *               application is requesting delegated access.
     */
    public function __construct(
        $serviceAccountName,
        $scopes,
        $privateKey,
        $privateKeyPassword = 'notasecret',
        $assertionType = 'http://oauth.net/grant_type/jwt/1.0/bearer',
        $sub = false
    ) {
        $this->serviceAccountName = $serviceAccountName;
        $this->scopes = \is_string($scopes) ? $scopes : implode(' ', $scopes);
        $this->privateKey = $privateKey;
        $this->privateKeyPassword = $privateKeyPassword;
        $this->assertionType = $assertionType;
        $this->sub = $sub;
        $this->prn = $sub;

        $this->accessToken = new AccessToken();
    }

    public function generateAssertion()
    {
        $now = \time();

        $jwtParams = array(
            'aud' => CredentialsLoader::TOKEN_CREDENTIAL_URI,
            'scope' => $this->scopes,
            'iat' => $now,
            'exp' => $now + self::MAX_TOKEN_LIFETIME_SECS,
            'iss' => $this->serviceAccountName,
        );

        if ($this->sub !== false) {
            $jwtParams['sub'] = $this->sub;
        } else if ($this->prn !== false) {
            $jwtParams['prn'] = $this->prn;
        }

        return $this->makeSignedJwt($jwtParams);
    }

    /**
     * Creates a signed JWT.
     * @param array $payload
     * @return string The signed JWT.
     */
    private function makeSignedJwt($payload)
    {
        $jwt = JWT::encode(
            $payload,
            '',
            'RS256'
        );
        $header = array('typ' => 'JWT', 'alg' => 'RS256');

        $segments = array(
            Utils::urlSafeB64Encode(json_encode($header)),
            Utils::urlSafeB64Encode(json_encode($payload))
        );

        $signingInput = implode('.', $segments);
        $signer = new P12Signer($this->privateKey, $this->privateKeyPassword);
        $signature = $signer->sign($signingInput);
        $segments[] = Utils::urlSafeB64Encode($signature);

        return implode(".", $segments);
    }
}
