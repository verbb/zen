<?php
namespace verbb\zen\fields;

use verbb\zen\Zen;
use verbb\zen\base\Field as ZenField;
use verbb\zen\helpers\ArrayHelper;
use verbb\zen\models\ElementDiffer;

use Craft;
use craft\base\ElementInterface;
use craft\base\FieldInterface;
use craft\elements\MatrixBlock;
use craft\fields\Matrix as MatrixField;

class BlockField extends ZenField
{
    // Static Methods
    // =========================================================================

    public static function getFieldForPreview(FieldInterface $field, ElementInterface $element, string $type): void
    {
        $fieldsService = Zen::$plugin->getFields();

        $value = $element->getFieldValue($field->handle);

        foreach ($value->all() as $block) {
            // Ensure all sub-fields are prepped for preview
            foreach ($fieldsService->getCustomFields($block->getType()) as $subField) {
                $fieldsService->getFieldForPreview($subField, $block, $type);
            }
        }

        parent::getFieldForPreview($field, $element, $type);
    }

    public static function beforeElementImport(FieldInterface $field, ElementInterface $element): bool
    {
        $fieldsService = Zen::$plugin->getFields();

        $value = $element->getFieldValue($field->handle);

        foreach ($value->all() as $block) {
            // Ensure we trigger inner fields' `beforeElementImport()`
            $fieldsService->beforeElementImport($block);
        }

        return parent::beforeElementImport($field, $element);
    }

    public static function afterElementImport(FieldInterface $field, ElementInterface $element): void
    {
        $fieldsService = Zen::$plugin->getFields();

        $value = $element->getFieldValue($field->handle);

        foreach ($value->all() as $block) {
            // Ensure we trigger inner fields' `beforeElementImport()`
            $fieldsService->beforeElementImport($block);
        }

        parent::afterElementImport($field, $element);
    }

    public static function handleValueForDiff(FieldInterface $field, mixed &$oldValue, mixed &$newValue): ?array
    {
        // By default, the ElementDiffer at the element-compare level won't be recursive into field data.
        // But for Matrix, we want to show the diff between blocks, so run our own diffs.
        $differ = new ElementDiffer();
        $diffs = [];

        if (is_array($oldValue) && is_array($newValue)) {
            foreach (ArrayHelper::getAllKeys($oldValue, $newValue) as $nestedKey) {
                $oldValueNested = $oldValue[$nestedKey] ?? [];
                $newValueNested = $newValue[$nestedKey] ?? [];

                $diff = $differ->doDiff($oldValueNested, $newValueNested);

                if ($diff !== null) {
                    $diffs[$nestedKey] = $diff;
                }
            }
        }

        return array_filter($diffs);
    }

}
