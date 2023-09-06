<?php

namespace DucksProject\Component\SimpleGoogleAnalyticsReporting;

use DucksProject\Component\SimpleGoogleAnalyticsReporting\Signer\P12Signer;
use Firebase\JWT\JWT;
use Google\Auth\AccessToken;
use Google\Auth\CredentialsLoader;
use Google\Auth\OAuth2;

/**
 *
 * @link https://github.com/googleapis/google-api-php-client/blob/v1.1.9/src/Google/Auth/AssertionCredentials.php
 * @link https://github.com/Nyholm/google-api-php-client/blob/master/src/GoogleApi/Auth/AssertionCredentials.php
 */
class AssertionCredentials extends CredentialsLoader
{
    public const MAX_TOKEN_LIFETIME_SECS = 3600;

    private AccessToken $auth;

    public string $serviceAccountName;
    public string $scopes;
    public $privateKey;
    public string $privateKeyPassword;
    public string $assertionType;
    public $sub;

    /**
     * @deprecated
     * @link http://tools.ietf.org/html/draft-ietf-oauth-json-web-token-06
     */
    public $prn;

    /**
     * @param string $serviceAccountName
     *      eg email
     * @param string|array $scopes
     *      array List of scopes
     * @param $privateKey
     * @param string $privateKeyPassword
     * @param string $assertionType
     * @param bool|string $sub The email address of the user for which the
     *      application is requesting delegated access.
     */
    public function __construct(
        string $serviceAccountName,
        $scopes,
        $privateKey,
        string $privateKeyPassword = 'notasecret',
        string $assertionType = 'http://oauth.net/grant_type/jwt/1.0/bearer',
        $sub = false
    ) {
        if (\is_string($privateKey)) {
            if (!file_exists($privateKey)) {
                throw new \InvalidArgumentException('file does not exist');
            }
            $jsonKeyStream = \file_get_contents($privateKey);
            if (!$jsonKey = json_decode((string) $jsonKeyStream, true)) {
                throw new \LogicException('invalid json for auth config');
            }
        }

        $this->serviceAccountName = $serviceAccountName;
        $this->scopes = \is_string($scopes) ? $scopes : implode(' ', $scopes);
        $this->privateKey = $privateKey;
        $this->privateKeyPassword = $privateKeyPassword;
        $this->assertionType = $assertionType;
        $this->sub = $sub;
        $this->prn = $sub;

        $this->auth = new OAuth2([
            'audience' => self::TOKEN_CREDENTIAL_URI,
            'issuer' => $serviceAccountName,
            'scope' => $this->scopes,
            'signingAlgorithm' => 'RS256',
            'signingKey' => $jsonKey['private_key'],
            'sub' => $sub,
            'tokenCredentialUri' => self::TOKEN_CREDENTIAL_URI,
            'additionalClaims' => $additionalClaims,
        ]);
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
        $this->auth->toJwt();
        $signer = new P12Signer($this->privateKey, $this->privateKeyPassword);

        $jwt = JWT::encode(
            $payload,
            $signer->sign(''),
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
