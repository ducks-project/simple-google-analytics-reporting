<?php

namespace DucksProject\Component\SimpleGoogleAnalyticsReporting\Enum;

// @php-ignore
class Scope extends \SplEnum
{
    // phpcs:ignore
    public const __default = '';

    /**
     * @link https://developers.google.com/analytics/devguides/reporting/core/v4/authorization
     */
    public const ANALYTICS = 'https://www.googleapis.com/auth/analytics';
    public const ANALYTICS_RO = 'https://www.googleapis.com/auth/analytics.readonly';
}
