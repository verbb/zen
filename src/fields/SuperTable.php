<?php
namespace verbb\zen\fields;

use verbb\zen\Zen;
use verbb\zen\base\Field as ZenField;
use verbb\zen\helpers\ArrayHelper;

use Craft;
use craft\base\ElementInterface;
use craft\base\FieldInterface;

use verbb\supertable\SuperTable as SuperTablePlugin;
use verbb\supertable\elements\SuperTableBlockElement;
use verbb\supertable\fields\SuperTableField;

class SuperTable extends ZenField
{
    // Static Methods
    // =========================================================================

    public static function fieldType(): string
    {
        return SuperTableField::class;
    }

    public static function serializeValue(FieldInterface $field, ElementInterface $element, mixed $value): mixed
    {
        // Swap IDs to UIDs for export
        $blocks = [];
        $new = 0;

        $fieldsService = Zen::$plugin->getFields();

        foreach ($value->all() as $block) {
            $serializedFieldValues = [];

            // Serialize all nested fields properly through Zen
            foreach ($block->getType()->getCustomFields() as $subField) {
                $subValue = $block->getFieldValue($subField->handle);

                $serializedFieldValues[$subField->handle] = $fieldsService->serializeValue($subField, $block, $subValue);
            }

            $blocks[] = [
                'type' => $block->getType()->uid,
                'uid' => $block->uid,
                'fields' => $serializedFieldValues,
            ];
        }

        return $blocks;
    }

    public static function normalizeValue(FieldInterface $field, ElementInterface $element, mixed $value): mixed
    {
        // Either find the existing block via UID, or set it as new
        $blocks = [];
        $new = 0;

        $blockTypes = ArrayHelper::index(SuperTablePlugin::$plugin->getService()->getAllBlockTypes(), 'uid');
        $fieldsService = Zen::$plugin->getFields();

        foreach ($value as $block) {
            $blockUid = $block['uid'] ?? null;

            if ($blockUid) {
                $existingBlock = SuperTableBlockElement::find()->uid($blockUid)->status(null)->one() ?? new SuperTableBlockElement();
            } else {
                $existingBlock = new SuperTableBlockElement();
            }

            $blockId = $existingBlock->id ?? 'new' . ++$new;

            $normalizedFieldValues = [];

            $blockTypeUid = $block['type'] ?? null;
            $blockType = $blockTypes[$blockTypeUid] ?? null;

            // Serialize all nested fields properly through Zen
            if ($blockType) {
                // Swap out the UID with the ID now it's been ported
                $block['type'] = $blockType->id;

                foreach ($blockType->getCustomFields() as $subField) {
                    $subValue = $block['fields'][$subField->handle] ?? null;

                    $normalizedFieldValues[$subField->handle] = $fieldsService->normalizeValue($subField, $existingBlock, $subValue);
                }
            }

            $block['fields'] = $normalizedFieldValues;

            $blocks[$blockId] = $block;
        }

        return $blocks;
    }

    public static function getFieldForPreview(FieldInterface $field, ElementInterface $element, string $type): void
    {
        $fieldsService = Zen::$plugin->getFields();

        $value = $element->getFieldValue($field->handle);

        foreach ($value->all() as $block) {
            // Ensure all sub-fields are prepped for preview
            foreach ($block->getType()->getCustomFields() as $subField) {
                $fieldsService->getFieldForPreview($subField, $block, $type);
            }
        }
    }

    public static function handleValueForDiffSummary(FieldInterface $field, mixed &$dest, mixed &$source): void
    {
        // Remove custom fields from the element used in element fields. They're just noise.
        $callback = function(&$item) {
            ArrayHelper::remove($item, 'fields');
        };

        if (is_array($dest)) {
            array_walk($dest, $callback);
        }

        if (is_array($source)) {
            array_walk($source, $callback);
        }
    }

}
