<?php

namespace DucksProject\Component\SimpleGoogleAnalyticsReporting;

use DucksProject\Component\SimpleGoogleAnalyticsReporting\Attribute\Row;
use DucksProject\Component\SimpleGoogleAnalyticsReporting\Attribute\Rows;
use DucksProject\Component\SimpleGoogleAnalyticsReporting\Request\ResponseInterface;
use DucksProject\Component\SimpleGoogleAnalyticsReporting\Request\RunReportResponse;

class Parser
{
    public function __construct()
    {
    }

    public function parseRequest(string $content): ?ResponseInterface
    {
        $json = \json_decode($content, true);

        if (null !== $json) {
            switch ($json['kind']) {
                case 'analytics#gaData':
                    $response = new RunReportResponse();
                    break;

                default:
                    return null;
                    break;
            }
        }

        if (!empty($json['rows']) && !empty($json['columnHeaders'])) {
            if (\count($json['rows'][0]) !== \count($json['columnHeaders'])) {
                throw new \LogicException('Mismatch between columns body and headers');
            }

            foreach ($json['row'] as $row) {
                $rows = (new Row())->fromData(\array_combine($json['columnHeaders'], $row));
            }
            $response->setRows(new Rows($rows ?? []));
        }

        return $response;
    }
}
