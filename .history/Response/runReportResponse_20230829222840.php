<?php

namespace DucksProject\Component\SimpleGoogleAnalyticsReporting\Request;

class RunReportResponse extends Response
{
    public function getKind(): string
    {
        return 'analytics#gaData';
    }
}
