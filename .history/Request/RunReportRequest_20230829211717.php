<?php

namespace DucksProject\Component\SimpleGoogleAnalyticsReporting\Request;

class RunReportRequest
{
    private string $property_id;

    private array $dataRanges;

    private array $dimensions;

    private array $metrics;

    private array $orberBys;

    private array $dimensionsFilters;

    private array $metricsFilters;

    private int $offset = 0;

    private int $limit = 0;

    public function __construct(string $property_id)
    {

    }
}
