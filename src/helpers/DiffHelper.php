<?php
namespace verbb\zen\helpers;

use Diff\DiffOp\DiffOpAdd;
use Diff\DiffOp\DiffOpChange;
use Diff\DiffOp\DiffOpRemove;
use Diff\DiffOp\Diff\Diff;

class DiffHelper
{
    // Static Methods
    // =========================================================================

    public static function convertDiffToArray(array $array): array
    {
        $newArray = [];

        foreach ($array as $attribute => $value) {
            if ($value instanceof Diff) {
                $newArray[$attribute] = self::convertDiffToArray($value->getOperations());
            } else {
                $newArray[$attribute] = $value->toArray();
            }
        }

        return $newArray;
    }

    public static function convertDiffToTypedArray(array $array): array
    {
        $newArray = [];

        foreach ($array as $attribute => $value) {
            if ($value instanceof DiffOpAdd) {
                $newArray['add'][$attribute] = $value->toArray();
            } else if ($value instanceof DiffOpChange) {
                $newArray['change'][$attribute] = $value->toArray();
            } else if ($value instanceof DiffOpRemove) {
                $newArray['remove'][$attribute] = $value->toArray();
            } else if ($value instanceof Diff) {
                $items = self::convertDiffToTypedArray($value->getOperations());

                foreach ($items as $action => $item) {
                    $items[$action][$attribute] = ArrayHelper::remove($items, $action);
                }

                $newArray = array_merge($newArray, $items);
            }
        }

        return $newArray;
    }

    public static function convertDiffToCount(array $array): array
    {
        $newArray = [];

        foreach ($array as $attribute => $value) {
            if ($value instanceof DiffOpAdd) {
                $newArray['add'] = ($newArray['add'] ?? 0) + 1;
            } else if ($value instanceof DiffOpChange) {
                $newArray['change'] = ($newArray['change'] ?? 0) + 1;
            } else if ($value instanceof DiffOpRemove) {
                $newArray['remove'] = ($newArray['remove'] ?? 0) + 1;
            } else if ($value instanceof Diff) {
                $items = self::convertDiffToCount($value->getOperations());

                foreach ($items as $action => $item) {
                    // We only really care (at the moment) about top-level fields not how many individual
                    //  diffs there are for nested arrays like relations, so just increment by one.
                    $newArray[$action] = ($newArray[$action] ?? 0) + 1;
                }
            }
        }

        return $newArray;
    }

    public static function convertDiffToFieldIndicators(array $array): array
    {
        $newArray = [];

        foreach ($array as $attribute => $value) {
            if ($value instanceof DiffOpAdd) {
                $newArray[$attribute] = 'add';
            } else if ($value instanceof DiffOpChange) {
                $newArray[$attribute] = 'change';
            } else if ($value instanceof DiffOpRemove) {
                $newArray[$attribute] = 'remove';
            } else if ($value instanceof Diff) {
                $items = self::convertDiffToFieldIndicators($value->getOperations());

                foreach ($items as $subAttribute => $action) {
                    $newArray[$attribute] = $action;
                    
                    // If the index is numeric, assume we just want to know about the top-level field
                    // Otherwise, it's something more complicated like Matrix
                    if (!is_integer($subAttribute)) {
                        $newArray[$attribute . '-' . $subAttribute] = $action;
                    }
                }
            }
        }

        return $newArray;
    }

}
