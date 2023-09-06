<?php

namespace DucksProject\Component\SimpleGoogleAnalyticsReporting\Credentials;

use DucksProject\Component\SimpleGoogleAnalyticsReporting\Encoder\P12Encoder;
use Google\Auth\CredentialsLoader;
use Google\Auth\OAuth2;
use Google\Auth\ServiceAccountSignerTrait;
use Google\Auth\SignBlobInterface;

/**
 *
 * @link https://github.com/googleapis/google-api-php-client/blob/v1.1.9/src/Google/Auth/AssertionCredentials.php
 * @link https://github.com/Nyholm/google-api-php-client/blob/master/src/GoogleApi/Auth/AssertionCredentials.php
 */
class AssertionCredentials extends CredentialsLoader implements SignBlobInterface
{
    use ServiceAccountSignerTrait;

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
     * @param string $targetAudience The audience for the ID token.
     */
    public function __construct(
        $scope,
        string $serviceAccountName,
        $privateKey,
        ?string $privateKeyPassword = 'notasecret',
        ?string $assertionType = 'http://oauth.net/grant_type/jwt/1.0/bearer',
        $sub = false,
        ?string $targetAudience = null
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
            throw new \InvalidArgumentException(
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
        $audience = $this->auth->getAudience();
        $scope = $this->auth->getScope();
        if (empty($audience) && empty($scope)) {
            return null;
        }

        if (!empty($audience) && !empty($scope)) {
            throw new \UnexpectedValueException(
                'Cannot sign both audience and scope in JwtAccess'
            );
        }

        $access_token = $this->auth->toJwt();

        // Set the self-signed access token in OAuth2 for getLastReceivedToken
        $this->auth->setAccessToken($access_token);

        return ['access_token' => $access_token];
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
        return $this->auth->getLastReceivedToken();
    }

    public function getClientName(callable $httpHandler = null)
    {
        return $this->auth->getIssuer();
    }
}
