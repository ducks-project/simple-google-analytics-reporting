<?php

namespace Ducks\Component\SimpleGoogleAnalyticsReporting\Gapi\Entry;

use Ducks\Component\SimpleGoogleAnalyticsReporting\Gapi\Silentable;
use Ducks\Component\SimpleGoogleAnalyticsReporting\Gapi\Traits\DynamicProperties;
use Ducks\Component\SimpleGoogleAnalyticsReporting\Gapi\Traits\Silent;

abstract class Base implements Silentable, \Stringable
{
    use Silent;
    use DynamicProperties;

    abstract public function __toString();
}
