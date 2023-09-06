<?php

namespace DucksProject\Component\SimpleGoogleAnalyticsReporting\Attribute;

trait DynamicTrait
{
    public function fromData(array $data): AttributeInterface
    {
        $data = \json_decode(\json_encode($data), true);

        foreach ($this->data as $key => $value) {
            if (\is_array($value)) {
                $this->$key = (new Dynamic())->fromData($value);
            } else {
                $this->$key = $value;
            }
        }

        return $this;
    }
}
