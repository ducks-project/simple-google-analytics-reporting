<?php

namespace DucksProject\Component\SimpleGoogleAnalyticsReporting\Signer;

interface SignerInterface
{
    public function sign($data);
}
