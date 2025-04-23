<?php
namespace verbb\zen\helpers;

use verbb\base\helpers\ArrayHelper as BaseArrayHelper;

class ArrayHelper extends BaseArrayHelper
{
    // Static Methods
    // =========================================================================

    public static function recursiveRemove(array &$array, string $value): void
    {
        // We only want to filter true empty values
        self::remove($array, $value);
        
        foreach ($array as &$item) {
            if (is_array($item)) {
                self::recursiveRemove($item, $value);
            }
        }
    }

    public static function getAllKeys(array $array1, array $array2): array
    {
        return array_unique(array_merge(
            array_keys($array1),
            array_keys($array2)
        ));
    }

}
