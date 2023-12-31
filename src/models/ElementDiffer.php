<?php
namespace verbb\zen\models;

use verbb\zen\Zen;
use verbb\zen\helpers\ArrayHelper;

use craft\base\Model;

class ElementDiffer extends Model
{
    // Public Methods
    // =========================================================================

    public function doDiff(array $oldValues, array $newValues): array
    {
        // Remove any values we don't need to diff against
        foreach (['parent', 'prevSibling', 'nextSibling'] as $ignore) {
            ArrayHelper::recursiveRemove($oldValues, $ignore);
            ArrayHelper::recursiveRemove($newValues, $ignore);
        }

        $newSet = $this->arrayDiffAssoc($newValues, $oldValues);
        $oldSet = $this->arrayDiffAssoc($oldValues, $newValues);

        $diffs = [];

        foreach (ArrayHelper::getAllKeys($oldSet, $newSet) as $key) {
            $diff = $this->getDiff($key, $oldSet, $newSet);

            if ($diff !== null) {
                $diffs[$key] = $diff;
            }
        }

        // Filter out any null or empty array items
        return array_filter($diffs, function($value) {
            return $value !== null && $value !== [];
        });
    }

    public function applyDiff(array $oldValues, array $diffs): array
    {
        foreach ($diffs as $key => $diff) {
            if (is_array($diff)) {
                $oldValues[$key] = $this->applyDiff(($oldValues[$key] ?? []), $diff);
            }

            if ($diff instanceof DiffAdd || $diff instanceof DiffChange) {
                $oldValues[$key] = $diff->newValue;
            }

            if ($diff instanceof DiffRemove) {
                unset($oldValues[$key]);
            }
        }

        return $oldValues;
    }

    public function getSummaryCount(array $diffs, array &$summary = ['add' => 0, 'change' => 0, 'remove' => 0]): array
    {
        foreach ($diffs as $key => $diff) {
            if (is_array($diff)) {
                $this->getSummaryCount($diff, $summary);
            }

            if ($diff instanceof DiffAdd) {
                $summary['add'] += 1;
            } else if ($diff instanceof DiffChange) {
                $summary['change'] += 1;
            } else if ($diff instanceof DiffRemove) {
                $summary['remove'] += 1;
            }
        }

        return array_filter($summary);
    }

    public function getSummaryFieldIndicators(array $diffs, array &$summary = [], string $parentKey = ''): array
    {
        foreach ($diffs as $key => $diff) {
            // Is this a custom field?
            if (str_contains($key, ':')) {
                $key = explode(':', $key)[0];
            }

            $index = $parentKey ? $parentKey . '.' . $key : $key;

            if (is_array($diff)) {
                $this->getSummaryFieldIndicators($diff, $summary, $index);
            }

            if ($diff instanceof DiffAdd) {
                $summary[$index] = ['type' => 'add'];
            } else if ($diff instanceof DiffChange) {
                $summary[$index] = ['type' => 'change', 'diffHtml' => $diff->getDiffHtml()];
            } else if ($diff instanceof DiffRemove) {
                $summary[$index] = ['type' => 'remove'];
            }
        }

        return $summary;
    }


    // Private Methods
    // =========================================================================

    private function getDiff(string|int $key, array $oldValues, array $newValues)
    {
        // We treat `null` values as empty, so add/remove should apply
        $oldValue = $oldValues[$key] ?? null;
        $newValue = $newValues[$key] ?? null;

        // Check if this is a custom field key (handle+uid), and if it's returning specific diffs
        if ($fieldDiff = Zen::$plugin->getFields()->handleValueForDiff($key, $oldValue, $newValue)) {
            return $fieldDiff;
        }

        // Fields may have altered the values, so check again in equal
        if ($oldValue === $newValue) {
            return null;
        }

        // Fields are the only thing we allow recursively
        if ($key === 'fields') {
            return $this->doDiff(($oldValue ?? []), ($newValue ?? []));
        }

        if ($this->isEmpty($oldValue) && !$this->isEmpty($newValue)) {
            return new DiffAdd(['newValue' => $newValue]);
        }

        if ($this->isEmpty($newValue) && !$this->isEmpty($oldValue)) {
            return new DiffRemove(['oldValue' => $oldValue]);
        }

        if (!$this->isEmpty($newValue) && !$this->isEmpty($oldValue)) {
            return new DiffChange(['oldValue' => $oldValue, 'newValue' => $newValue]);
        }

        return null;
    }

    private function arrayDiffAssoc(array $from, array $to): array
    {
        $diff = [];

        foreach ($from as $key => $value) {
            if (!array_key_exists($key, $to) || $to[$key] !== $value) {
                $diff[$key] = $value;
            }
        }

        return $diff;
    }

    private function isEmpty(mixed $value): bool
    {
        return $value === null || $value === [];
    }
}
