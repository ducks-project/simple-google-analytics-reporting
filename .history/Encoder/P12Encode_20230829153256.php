<?php

namespace DucksProject\Component\SimpleGoogleAnalyticsReporting\Encoder;

class P12Encode implements EncoderInterface
{
    public function __construct()
    {
        if (!\function_exists('openssl_pkcs12_read')) {
            throw new \Exception(
                'The Google PHP API library needs the openssl PHP extension'
            );
        }
    }

    public function encode($data, array $context = [])
    {
        $output = null;
        \openssl_pkcs12_export(
            $data,
            $output,
            $data,
            $data
        );

        return $output;
    }
}
