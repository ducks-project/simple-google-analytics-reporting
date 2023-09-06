<?php

/**
 * Polyfill.
 *
 * (c) Adrien Loyant <donald_duck@team-df.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

$namespace = '\\Ducks\\Component\\SimpleGoogleAnalyticsReporting\\Gapi\\';

$gapi = [
    'gapi',
    'gapiAccountEntry',
    'gapiReportEntry',
    'gapiOAuth2',
    'gapiRequest',
];
foreach ($gapi as $class) {
    if (!\class_exists($class, false)) {
        $class_name = \substr($class, 4) ?: 'Service';
        \class_alias($namespace. $class_name, $class, true);
    }
}

$entries = [
    'gapiAccountEntry',
    'gapiReportEntry',
];
foreach ($entries as $class) {
    if (!\class_exists($class, false)) {
        $class_name = \substr($class, 4, -5);
        \class_alias($namespace . 'Entry\\' . $class_name, $class, true);
    }
}
