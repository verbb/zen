<?php
namespace verbb\zen\fields;

use verbb\zen\Zen;
use verbb\zen\base\Field as ZenField;

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

        foreach ($value as $blockUid => $block) {
            $foundBlock = MatrixBlock::find()->uid($blockUid)->status(null)->one();
            $blockId = $foundBlock->id ?? 'new' . ++$new;

            $blocks[$blockId] = $block;
        }

        return $blocks;
    }

}
