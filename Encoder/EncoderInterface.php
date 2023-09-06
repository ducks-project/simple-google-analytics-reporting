<?php

namespace DucksProject\Component\SimpleGoogleAnalyticsReporting\Encoder;

interface EncoderInterface
{
    /**
     * Encodes data into the given format.
     *
     * @param mixed  $data    Data to encode
     * @param array  $context Options that normalizers/encoders have access to
     *
     * @return string|int|float|bool
     */
    public function encode($data, array $context = []);
}
