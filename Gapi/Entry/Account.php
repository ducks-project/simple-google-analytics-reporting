<?php

namespace Ducks\Component\SimpleGoogleAnalyticsReporting\Gapi\Entry;

class Account extends Base
{
    private $properties = [];

    /**
     * Constructor function for all new gapiAccountEntry instances.
     *
     * @param array $properties
     *
     * @return self
     */
    public function __construct($properties)
    {
        $this->properties = $properties;
    }

    /**
     * toString function to return the name of the account.
     *
     * @return string
     */
    public function __toString()
    {
        return $this->properties['name'] ?? '';
    }

    /**
     * Get an associative array of the properties
     * and the matching values for the current result.
     *
     * @return array
     */
    public function getProperties()
    {
        return $this->properties;
    }
}
