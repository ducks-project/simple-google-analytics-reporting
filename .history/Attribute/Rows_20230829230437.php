<?php

namespace DucksProject\Component\SimpleGoogleAnalyticsReporting\Attribute;

use Symfony\Component\String\UnicodeString;

#[\AllowDynamicProperties]
class Rows extends \ArrayObject implements AttributeInterface
{
    use DynamicTrait;

    public function addRow(Row $row)
    {
        $this->append($row);
    }

    public function __call($name, $arguments)
    {
        $name = (string) ((new UnicodeString($name))->snake());
        if (!\is_callable($name) || \substr($name, 0, 6) !== 'array_') {
            throw new \BadMethodCallException(static::class . '->' . $name);
        }

        return \call_user_func_array($name, \array_merge([$this->getArrayCopy()], $arguments));
    }
}