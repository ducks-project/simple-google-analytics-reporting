<?php

namespace DucksProject\Component\SimpleGoogleAnalyticsReporting\Gapi\Entry;

use DucksProject\Component\SimpleGoogleAnalyticsReporting\Gapi\Silentable;
use DucksProject\Component\SimpleGoogleAnalyticsReporting\Gapi\Traits\DynamicProperties;
use DucksProject\Component\SimpleGoogleAnalyticsReporting\Gapi\Traits\Silent;

abstract class Base implements Silentable, \Stringable
{
    use Silent;
    use DynamicProperties;

    abstract public function __toString();
}
