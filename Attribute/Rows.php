<?php

namespace DucksProject\Component\SimpleGoogleAnalyticsReporting\Attribute;

use Symfony\Component\String\UnicodeString;

class Rows extends Collection
{
    public function addRow(Row $row)
    {
        $this->append($row);
    }
}
