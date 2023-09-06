<?php

namespace DucksProject\Component\SimpleGoogleAnalyticsReporting\Encoder;

interface SignerInterface
{
    public function sign($data);
}
