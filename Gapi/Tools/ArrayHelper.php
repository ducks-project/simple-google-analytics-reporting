<?php

namespace Ducks\Component\SimpleGoogleAnalyticsReporting\Gapi\Tools;

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
    public static function arrayKeyExists($key, $search)
    {
        if (!\is_iterable($search) || !\is_string($key)) {
            return false;
        }

        if (\array_key_exists($key, $search)) {
            return $key;
        }

        // Note that array_change_key_case will not offer
        // possibility to return correct case and will be O(n)
        // \array_key in foreach will also do a copy of array...
        $key = \strtolower($key);
        foreach ($search as $search_key => $value) {
            if ($key === \strtolower($search_key)) {
                return $search_key;
            }
        }

        return false;
    }
}
