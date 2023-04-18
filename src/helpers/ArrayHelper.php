<?php
namespace verbb\zen\helpers;

use craft\helpers\ArrayHelper as CraftArrayHelper;

class ArrayHelper extends CraftArrayHelper
{
    // Static Methods
    // =========================================================================

    public static function expand(array $data, string $separator = '.'): array
    {
        $hash = [];

        foreach ($data as $path => $value) {
            $keys = explode($separator, (string)$path);

            if (count($keys) === 1) {
                $hash[$path] = $value;
                continue;
            }

            $valueKey = end($keys);
            $keys = array_slice($keys, 0, -1);

            $keyHash = &$hash;

            foreach ($keys as $key) {
                if (!array_key_exists($key, $keyHash)) {
                    $keyHash[$key] = [];
                }

                $keyHash = &$keyHash[$key];
            }

            $keyHash[$valueKey] = $value;
        }

        return $hash;
    }

    public static function recursiveFilter(array $array)
    {
        // We only want to filter true empty values
        $array = array_filter($array, function($val) {
            return ($val !== null && $val !== '');
        });
        
        foreach ($array as &$value) {
            if (is_array($value)) {
                $value = self::recursiveFilter($value);
            }
        }

        return $array;
    }

}
