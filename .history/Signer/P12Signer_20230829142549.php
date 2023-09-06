<?php

namespace DucksProject\Component\SimpleGoogleAnalyticsReporting\Signer;

class P12Signer implements SignerInterface
{
    // OpenSSL private key resource
    private $privateKey;

    // Creates a new signer from a .p12 file.
    public function __construct($p12, $password)
    {
        if (!\function_exists('openssl_pkcs12_read')) {
            throw new \Exception(
                'The Google PHP API library needs the openssl PHP extension'
            );
        }

        // This throws on error
        $certs = [];
        if (!\openssl_pkcs12_read($p12, $certs, $password)) {
            throw new \DomainException("Unable to parse the p12 file.  " .
                "Is this a .p12 file?  Is the password correct?  OpenSSL error: " .
                \openssl_error_string());
        }

        if (empty($certs["pkey"])) {
            throw new \DomainException("No private key found in p12 file.");
        }

        $this->privateKey = \openssl_pkey_get_private($certs["pkey"]);
        if (!$this->privateKey) {
            throw new AuthException("Unable to load private key in ");
        }
    }

    public function sign($data)
    {
        if (!openssl_sign($data, $signature, $this->privateKey, "sha256")) {
            throw new \DomainException("Unable to sign data");
        }

        return $signature;
    }
}
