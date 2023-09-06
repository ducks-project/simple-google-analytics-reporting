<?php

namespace DucksProject\Component\SimpleGoogleAnalyticsReporting\Encoder;

class P12Encoder implements EncoderInterface, DecoderInterface
{
    public const FORMAT = 'p12';

    protected EncoderInterface $encoding;
    protected DecoderInterface $decoding;

    public function __construct()
    {
        $this->encoding = new P12Encode();
        $this->decoding = new P12Decode();
    }

    /**
     * {@inheritdoc}
     */
    public function encode($data, array $context = [])
    {
        return $this->encoding->encode($data, $context);
    }

    /**
     * {@inheritdoc}
     */
    public function decode(string $data, array $context = [])
    {
        return $this->decoding->decode($data, $context);
    }
}
