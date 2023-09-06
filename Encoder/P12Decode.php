<?php

namespace DucksProject\Component\SimpleGoogleAnalyticsReporting\Encoder;

class P12Decode implements DecoderInterface
{
    public function __construct()
    {
        if (!\function_exists('openssl_pkcs12_read')) {
            throw new \Exception(
                'The Google PHP API library needs the openssl PHP extension'
            );
        }
    }

    public function decode(string $data, array $context = [])
    {
        $certs = [];
        if (!\openssl_pkcs12_read($data, $certs, $context['passphrase'] ?? '')) {
            throw new \DomainException(
                "Unable to parse the p12 file.  " .
                "Is this a .p12 file?  Is the password correct?  OpenSSL error: " .
                \openssl_error_string()
            );
        }

        return $certs;
    }
}
