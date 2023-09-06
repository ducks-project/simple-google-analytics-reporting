<?php

namespace DucksProject\Component\SimpleGoogleAnalyticsReporting\Attribute;

interface AttributeInterface
{
    public function fromData(array $data): AttributeInterface;
}
