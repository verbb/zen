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
            $serializedFieldValues = [];

            // Serialize all nested fields properly through Zen
            foreach ($fieldsService->getCustomFields($block->getType()) as $subField) {
                $subValue = $block->getFieldValue($subField->handle);

                $serializedFieldValues[$subField->handle] = $fieldsService->serializeValue($subField, $block, $subValue);
            }

            $blocks[] = [
                'type' => $block->getType()->uid,
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

        $blockTypes = ArrayHelper::index(Craft::$app->getMatrix()->getAllBlockTypes(), 'uid');
        $fieldsService = Zen::$plugin->getFields();

        foreach ($value as $block) {
            $blockUid = $block['uid'] ?? null;

            if ($blockUid) {
                $existingBlock = MatrixBlock::find()->uid($blockUid)->status(null)->one() ?? new MatrixBlock();
            } else {
                $existingBlock = new MatrixBlock();
            }

            $blockId = $existingBlock->id ?? 'new' . ++$new;

            $normalizedFieldValues = [];

            $blockTypeUid = $block['type'] ?? null;
            $blockType = $blockTypes[$blockTypeUid] ?? null;

            // Serialize all nested fields properly through Zen
            if ($blockType) {
                // Swap out the UID with the ID now it's been ported
                $block['type'] = $blockType->handle;

                foreach ($fieldsService->getCustomFields($blockType) as $subField) {
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
            foreach ($fieldsService->getCustomFields($block->getType()) as $subField) {
                $fieldsService->getFieldForPreview($subField, $block, $type);
            }
        }
    }

}
