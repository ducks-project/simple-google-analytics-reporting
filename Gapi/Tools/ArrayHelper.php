<?php

namespace DucksProject\Component\SimpleGoogleAnalyticsReporting\Gapi\Tools;

/**
 * @deprecated 1.0
 */
abstract class ArrayHelper
{
    /**
     * Case insensitive array_key_exists function, also returns
     * matching key.
     *
     * @param string $key
     * @param array $search
     *
     * @return string Matching array key
     */
    public static function ArrayKeyExists($key, $search)
    {
        if (array_key_exists($key, $search)) {
            return $key;
        }
        if (!(is_string($key) && is_array($search))) {
            return false;
        }
        $key = strtolower($key);
        foreach ($search as $k => $v) {
            if (strtolower($k) == $key) {
                return $k;
            }
        }
        return false;
    }
}
