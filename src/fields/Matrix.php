<?php
namespace verbb\zen\fields;

use verbb\zen\Zen;
use verbb\zen\base\Field as ZenField;
use verbb\zen\helpers\ArrayHelper;

use Craft;
use craft\base\ElementInterface;
use craft\base\FieldInterface;
use craft\elements\MatrixBlock;
use craft\fields\Matrix as MatrixField;

class Matrix extends ZenField
{
    // Static Methods
    // =========================================================================

    public static function fieldType(): string
    {
        return MatrixField::class;
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
                'collapsed' => $block->collapsed,
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

        $blockTypes = ArrayHelper::index(Craft::$app->getMatrix()->getAllBlockTypes(), 'handle');
        $fieldsService = Zen::$plugin->getFields();

        foreach ($value as $blockUid => $block) {
            $foundBlock = MatrixBlock::find()->uid($blockUid)->status(null)->one();
            $blockId = $foundBlock->id ?? 'new' . ++$new;

            $normalizedFieldValues = [];

            $blockType = $blockTypes[$block['type']] ?? null;

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

}
