<?php

namespace DucksProject\Component\SimpleGoogleAnalyticsReporting\Encoder;

interface DecoderInterface
{
    /**
     * Decodes data.
     *
     * @param string $data The encoded JSON string to decode
     * @param array $context An optional set of options for the JSON decoder; see below
     *
     * @return mixed
     */
    public function decode(string $data, array $context = []);
}
