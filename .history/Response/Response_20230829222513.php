<?php

namespace DucksProject\Component\SimpleGoogleAnalyticsReporting\Request;

use Symfony\Component\String\UnicodeString;

abstract class Response implements ResponseInterface
{
    abstract public function getKind(): string;

    public function __get($name)
    {
    }

    public function __call($name, $arguments)
    {
        if ('get' === \substr($name, 0, 3)) {
            $name = (new UnicodeString($name))->camel();
        }
    }

    public function __toString()
    {
        return (string) \json_encode([
            'kind' => $this->getKind()
        ]);
    }
}
