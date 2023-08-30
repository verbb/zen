<?php
namespace verbb\zen\helpers;

use verbb\zen\Zen;

use Diff\DiffOp\DiffOpAdd;
use Diff\DiffOp\DiffOpChange;
use Diff\DiffOp\DiffOpRemove;
use Diff\DiffOp\Diff\Diff;

class DiffHelper
{
    // Static Methods
    // =========================================================================

    public static function getDiffSummary(array $diffData): array
    {
        $summaries = [];
        $summary = [];

        $fieldService = Zen::$plugin->getFields();

        // Because the serialized content will contain lots of extra info that we don't want to report on user-facing, 
        // we select just the attributes that are (user-facing) and check their add/change/remove state.
        // We also need special handling for fields, which have the same scenario. Especially for complex fields like Matrix.
        if ($diffData) {
            $destItem = $diffData[0] ?? [];
            $sourceItem = $diffData[1] ?? [];

            // Get the combined keys from the serialized objects
            $attributes = array_values(array_unique(array_merge(array_keys($destItem), array_keys($sourceItem))));

            // Remove anything we don't need
            ArrayHelper::removeValue($attributes, 'parent');
            ArrayHelper::removeValue($attributes, 'fields');

            $attributes = array_values($attributes);

            // Fields need to be treated differently
            $destFields = ArrayHelper::remove($destItem, 'fields');
            $sourceFields = ArrayHelper::remove($sourceItem, 'fields');

            foreach ($attributes as $attribute) {
                $dest = $destItem[$attribute] ?? null;
                $source = $sourceItem[$attribute] ?? null;

                // Give fields a chance (if they are registered) to handle getting the summary.
                $fieldService->handleValueForDiffSummary($attribute, $dest, $source);

                if ($dest === null) {
                    $summaries['add'][$attribute] = $source;
                    $summary['add'] = ($summary['add'] ?? 0) + 1;
                } else if ($source === null) {
                    $summaries['remove'][$attribute] = $dest;
                    $summary['remove'] = ($summary['remove'] ?? 0) + 1;
                } else if ($source !== $dest) {
                    // Special-case for element fields, which will be an array, which is "empty".
                    // Note we can't combine (easily) into the above (`$dest === null || $dest === []`).
                    // For example, when both source and destination are `[]` that'll be incorrectly marked as an addition.
                    if ($source === []) {
                        $summaries['remove'][$attribute] = $dest;
                        $summary['remove'] = ($summary['remove'] ?? 0) + 1;
                    } else if ($dest === []) {
                        $summaries['add'][$attribute] = $source;
                        $summary['add'] = ($summary['add'] ?? 0) + 1;
                    } else {
                        $summaries['change'][$attribute] = [$source, $dest];
                        $summary['change'] = ($summary['change'] ?? 0) + 1;
                    }
                }
            }

            // Sort out custom fields
            if ($destFields || $sourceFields) {
                foreach (self::getDiffSummary([$destFields, $sourceFields]) as $key => $value) {
                    $summary[$key] = ($summary[$key] ?? 0) + $value;
                }
            }
        }

        return $summary;
    }

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

        foreach ($array as $value) {
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
                    $newArray[$action] = ($newArray[$action] ?? 0) + $item;
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
                    if (!is_int($subAttribute)) {
                        $newArray[$attribute . '-' . $subAttribute] = $action;
                    }
                }
            }
        }

        return $newArray;
    }

}
