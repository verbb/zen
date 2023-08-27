<?php
namespace verbb\zen\fields;

use verbb\zen\Zen;
use verbb\zen\base\Field as ZenField;
use verbb\zen\helpers\ArrayHelper;

use Craft;
use craft\base\ElementInterface;
use craft\base\FieldInterface;

use benf\neo\elements\Block as NeoBlock;
use benf\neo\Field as NeoField;
use benf\neo\Plugin as Neo;

class Neo extends ZenField
{
    // Static Methods
    // =========================================================================

    public static function fieldType(): string
    {
        return NeoField::class;
    }

    public static function serializeValue(FieldInterface $field, ElementInterface $element, mixed $value): mixed
    {
        // Swap IDs to UIDs for export
        $blocks = [];
        $new = 0;

        $fieldsService = Zen::$plugin->getFields();

        foreach ($value->all() as $block) {
            $blockId = $block->uid ?? 'new' . ++$new;

            $serializedFieldValues = [];

            // Serialize all nested fields properly through Zen
            foreach ($block->getType()->getCustomFields() as $subField) {
                $subValue = $block->getFieldValue($subField->handle);

                $serializedFieldValues[$subField->handle] = $fieldsService->serializeValue($subField, $block, $subValue);
            }

            $blocks[$blockId] = [
                'type' => $block->getType()->handle,
                'enabled' => $block->enabled,
                'collapsed' => $block->getCollapsed(),
                'level' => $block->level,
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

        $blockTypes = ArrayHelper::index(Neo::$plugin->blockTypes->getAllBlockTypes(), 'handle');
        $fieldsService = Zen::$plugin->getFields();

        foreach ($value as $blockUid => $block) {
            $foundBlock = NeoBlock::find()->uid($blockUid)->status(null)->one() ?? new NeoBlock();
            $blockId = $foundBlock->id ?? 'new' . ++$new;

            $normalizedFieldValues = [];

            $blockTypeUid = $block['type'] ?? null;
            $blockType = $blockTypes[$blockTypeUid] ?? null;

            // Serialize all nested fields properly through Zen
            if ($blockType) {
                foreach ($blockType->getCustomFields() as $subField) {
                    $subValue = $block['fields'][$subField->handle] ?? null;

                    $normalizedFieldValues[$subField->handle] = $fieldsService->normalizeValue($subField, $foundBlock, $subValue);
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
