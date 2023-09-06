<?php

namespace DucksProject\Component\SimpleGoogleAnalyticsReporting\Credentials;

use DomainException;
use DucksProject\Component\SimpleGoogleAnalyticsReporting\Encoder\P12Encoder;
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

    private OAuth2 $auth;

    public $privateKey;
    public string $assertionType;
    public $sub;

    /**
     * @deprecated
     * @link http://tools.ietf.org/html/draft-ietf-oauth-json-web-token-06
     */
    public $prn;

    /**
     * @param string $serviceAccountName
     *      eg client_email
     * @param string|array $scopes
     *      array List of scopes
     * @param $privateKey
     * @param string $privateKeyPassword
     * @param string $assertionType
     * @param bool|string $sub The email address of the user for which the
     *      application is requesting delegated access.
     */
    public function __construct(
        $scope,
        string $serviceAccountName,
        $privateKey,
        string $privateKeyPassword = 'notasecret',
        string $assertionType = 'http://oauth.net/grant_type/jwt/1.0/bearer',
        $sub = false,
        $targetAudience = null
    ) {
        $encoder = new P12Encoder();

        if (\is_string($privateKey)) {
            if (!file_exists($privateKey)) {
                throw new \InvalidArgumentException('file does not exist');
            }
            $p12KeyStream = \file_get_contents($privateKey);
            if (!$p12Key = $encoder->decode((string) $p12KeyStream, ['passphrase' => $privateKeyPassword])) {
                throw new \LogicException('invalid p12 for auth config');
            }
        }

        if (empty($p12Key["pkey"])) {
            throw new \InvalidArgumentException(
                'p12 key is missing the pkey field'
            );
        }

        $this->privateKey = \openssl_pkey_get_private($p12Key["pkey"]);
        if (!$this->privateKey) {
            throw new \DomainException("Unable to load private key");
        }

        $this->assertionType = $assertionType;
        $this->sub = $sub;
        $this->prn = $sub;

        if ($scope && $targetAudience) {
            throw new InvalidArgumentException(
                'Scope and targetAudience cannot both be supplied'
            );
        }
        $additionalClaims = [];
        if ($targetAudience) {
            $additionalClaims = ['target_audience' => $targetAudience];
        }

        $this->auth = new OAuth2([
            'audience' => self::TOKEN_CREDENTIAL_URI,
            'issuer' => $serviceAccountName,
            'scope' => $scope,
            'signingAlgorithm' => 'RS256',
            'signingKey' => $this->privateKey,
            'sub' => $sub,
            'tokenCredentialUri' => self::TOKEN_CREDENTIAL_URI,
            'additionalClaims' => $additionalClaims,
        ]);
    }

    public function fetchAuthToken(callable $httpHandler = null)
    {
        // if ($this->useSelfSignedJwt()) {
        //     $jwtCreds = $this->createJwtAccessCredentials();

        //     $accessToken = $jwtCreds->fetchAuthToken($httpHandler);

        //     if ($lastReceivedToken = $jwtCreds->getLastReceivedToken()) {
        //         // Keep self-signed JWTs in memory as the last received token
        //         $this->lastReceivedJwtAccessToken = $lastReceivedToken;
        //     }

        //     return $accessToken;
        // }
        // return $this->auth->fetchAuthToken($httpHandler);
    }

    /**
     * @return string
     */
    public function getCacheKey(): string
    {
        $key = $this->auth->getIssuer() . ':' . $this->auth->getCacheKey();
        if ($sub = $this->auth->getSub()) {
            $key .= ':' . $sub;
        }

        return $key;
    }

    /**
     * @return array<mixed>
     */
    public function getLastReceivedToken()
    {
        // If self-signed JWTs are being used, fetch the last received token
        // from memory. Else, fetch it from OAuth2
        // return $this->useSelfSignedJwt()
        //     ? $this->lastReceivedJwtAccessToken
        //     : $this->auth->getLastReceivedToken();
    }

    /**
     * @return ServiceAccountJwtAccessCredentials
     */
    private function createJwtAccessCredentials()
    {
        if (!$this->jwtAccessCredentials) {
            // Create credentials for self-signing a JWT (JwtAccess)
            $credJson = [
                'private_key' => $this->auth->getSigningKey(),
                'client_email' => $this->auth->getIssuer(),
            ];
            $this->jwtAccessCredentials = new ServiceAccountJwtAccessCredentials(
                $credJson,
                $this->auth->getScope()
            );
        }

        return $this->jwtAccessCredentials;
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
