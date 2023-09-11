<?php

namespace Ducks\Component\SimpleGoogleAnalyticsReporting\Gapi\Traits;

use Ducks\Component\SimpleGoogleAnalyticsReporting\Gapi\Tools\ArrayHelper;

trait DynamicProperties
{
    abstract public function isSilent(): bool;

    protected function getDynamicVars(): array
    {
        return \array_keys(\get_object_vars($this));
    }

    public function __call($name, $parameters)
    {
        if (!\preg_match('/^get(?<property>.*)$/', $name, $matches)) {
            if (!$this->isSilent()) {
                throw new \Exception('No such function "' . $name . '"');
            } else {
                return null;
            }
        }

        $property = $matches['property'];
        foreach ($this->getDynamicVars() as $value) {
            $key = ArrayHelper::arrayKeyExists($property, $this->$value);
            if (false !== $key) {
                return ($this->$value)[$key];
            }
        }

        if (!$this->isSilent()) {
            throw new \Exception('No valid property called "' . $name . '"');
        }
    }
}
