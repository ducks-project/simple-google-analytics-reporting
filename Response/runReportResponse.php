<?php

namespace DucksProject\Component\SimpleGoogleAnalyticsReporting\Request;

use DucksProject\Component\SimpleGoogleAnalyticsReporting\Attribute\Rows;

class RunReportResponse extends Response
{
    protected ?Rows $rows = null;
    protected ?Total $total = null;

    public function getKind(): string
    {
        return 'analytics#gaData';
    }

    public function setRows(Rows $rows): self
    {
        $this->rows = $rows;

        return $this;
    }

    //public function setTotal(Total )
}
