<?php

namespace DucksProject\Component\SimpleGoogleAnalyticsReporting\Enum;

// @php-ignore
/**
 *
 * @link https://github.com/googleapis/google-api-php-client-services/blob/main/src/Analytics.php
 */
class Scope extends \SplEnum
{
    // phpcs:ignore
    public const __default = '';

    public const ANALYTICS = 'https://www.googleapis.com/auth/analytics';
    public const ANALYTICS_RO = self::ANALYTICS_READONLY;
    public const ANALYTICS_READONLY = 'https://www.googleapis.com/auth/analytics.readonly';
    public const ANALYTICS_EDIT = 'https://www.googleapis.com/auth/analytics.edit';
    public const ANALYTICS_MANAGE_USERS = 'https://www.googleapis.com/auth/analytics.manage.users';
    public const ANALYTICS_MANAGE_USERS_RO = self::ANALYTICS_MANAGE_USERS_RO;
    public const ANALYTICS_MANAGE_USERS_READONLY = 'https://www.googleapis.com/auth/analytics.manage.users.readonly';
    public const ANALYTICS_PROVISION = 'https://www.googleapis.com/auth/analytics.provision';
    public const ANALYTICS_USER_DELETION = 'https://www.googleapis.com/auth/analytics.user.deletion';
}
