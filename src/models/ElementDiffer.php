<?php
namespace verbb\zen\models;

use verbb\zen\helpers\ArrayHelper;

use craft\base\Model;

class ElementDiffer extends Model
{
    // Public Methods
    // =========================================================================

    public function doDiff(array $oldValues, array $newValues): array
    {
        // Remove any values we don't need to diff against
        ArrayHelper::remove($oldValues, 'parent');
        ArrayHelper::remove($newValues, 'parent');

        $newSet = $this->arrayDiffAssoc($newValues, $oldValues);
        $oldSet = $this->arrayDiffAssoc($oldValues, $newValues);

        $diffs = [];

        foreach ($this->getAllKeys($oldSet, $newSet) as $key) {
            $diff = $this->getDiff($key, $oldSet, $newSet);

            if ($diff !== null) {
                $diffs[$key] = $diff;
            }
        }

        return $diffs;
    }

    public function applyDiff(array $oldValues, array $diffs): array
    {
        foreach ($diffs as $key => $diff) {
            if ($key === 'fields') {
                $oldValues[$key] = $this->applyDiff($oldValues[$key], $diff);
            }

            if ($diff instanceof DiffAdd || $diff instanceof DiffChange) {
                $oldValues[$key] = $diff['newValue'];
            }

            if ($diff instanceof DiffRemove) {
                unset($oldValues[$key]);
            }
        }

        return $oldValues;
    }

    public function getSummaryCount(array $diffs, array $summary = ['add' => 0, 'change' => 0, 'remove' => 0]): array
    {
        foreach ($diffs as $key => $diff) {
            if ($key === 'fields') {
                return $this->getSummaryCount($diff, $summary);
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

    public function getSummaryFieldIndicators(array $diffs): array
    {
        $summary = [];

        foreach ($diffs as $key => $diff) {
            if ($key === 'fields') {
                $items = $this->getSummaryFieldIndicators($diff);

                foreach ($items as $subKey => $action) {
                    $summary[$key] = $action;

                    // If the index is numeric, assume we just want to know about the top-level field
                    // Otherwise, it's something more complicated like Matrix
                    if (!is_int($subKey)) {
                        $summary[$key . '-' . $subKey] = $action;
                    }
                }
            }

            if ($diff instanceof DiffAdd) {
                $summary[$key] = 'add';
            } else if ($diff instanceof DiffChange) {
                $summary[$key] = 'change';
            } else if ($diff instanceof DiffRemove) {
                $summary[$key] = 'remove';
            }
        }

        return $summary;
    }


    // Private Methods
    // =========================================================================

    private function getAllKeys(array $oldValues, array $newValues): array
    {
        return array_unique(array_merge(
            array_keys($oldValues),
            array_keys($newValues)
        ));
    }

    private function getDiff(string $key, array $oldValues, array $newValues)
    {
        // We treat `null` values as empty, so add/remove should apply
        $oldValue = $oldValues[$key] ?? null;
        $newValue = $newValues[$key] ?? null;

        // Fields are the only thing we allow recursively
        if ($key === 'fields') {
            return $this->doDiff(($oldValue ?? []), ($newValue ?? []));
        }

        if ($this->isEmpty($oldValue) && $newValue) {
            return new DiffAdd(['newValue' => $newValue]);
        }

        if ($this->isEmpty($newValue) && $oldValue) {
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
