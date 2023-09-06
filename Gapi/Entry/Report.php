<?php

namespace DucksProject\Component\SimpleGoogleAnalyticsReporting\Gapi\Entry;

class Report extends Base
{
    private $metrics = [];
    private $dimensions = [];

    /**
     * Constructor function for all new gapiReportEntry instances.
     *
     * @param array $metrics
     * @param array $dimensions
     *
     * @return gapiReportEntry
     */
    public function __construct($metrics, $dimensions)
    {
        $this->metrics = $metrics;
        $this->dimensions = $dimensions;
    }

    /**
     * toString function to return the name of the result
     * this is a concatented string of the dimensions chosen.
     *
     * For example:
     * 'Firefox 3.0.10' from browser and browserVersion
     *
     * @return string
     */
    public function __toString()
    {
        return \trim(\implode(' ', $this->dimensions ?? []));
    }

    /**
     * Get an associative array of the dimensions
     * and the matching values for the current result.
     *
     * @return array
     */
    public function getDimensions()
    {
        return $this->dimensions;
    }

    /**
     * Get an array of the metrics and the matchning
     * values for the current result.
     *
     * @return array
     */
    public function getMetrics()
    {
        return $this->metrics;
    }
}
