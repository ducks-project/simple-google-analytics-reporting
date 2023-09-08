<?php

namespace Ducks\Component\SimpleGoogleAnalyticsReporting\Gapi\Traits;

use Ducks\Component\SimpleGoogleAnalyticsReporting\Gapi\Tools\ArrayHelper;

trait DynamicProperties
{
    abstract public function isSilent(): bool;

    protected function getObjectVars(): array
    {
        return \get_object_vars($this) ?? [];
    }

    public function __call($name, $parameters)
    {
        if (!preg_match('/^get/', $name)) {
            if (!$this->isSilent()) {
                throw new \Exception('No such function "' . $name . '"');
            } else {
                return null;
            }
        }

        $name = preg_replace('/^get/', '', $name);
        foreach ($this->getObjectVars() as $value) {
            $key = ArrayHelper::arrayKeyExists($name, $this->$value);
            if (false !== $key) {
                return ($this->$value)[$key];
            }
        }

        if (!$this->isSilent()) {
            throw new \Exception('No valid property called "' . $name . '"');
        }
    }
}
