<?php
namespace verbb\zen\fields;

use verbb\zen\Zen;
use verbb\zen\base\Field as ZenField;
use verbb\zen\helpers\ArrayHelper;
use verbb\zen\models\ElementDiffer;

use Craft;
use craft\base\ElementInterface;
use craft\base\FieldInterface;

use verbb\supertable\SuperTable as SuperTablePlugin;
use verbb\supertable\elements\SuperTableBlockElement;
use verbb\supertable\fields\SuperTableField;

class SuperTable extends BlockField
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
            foreach ($fieldsService->getCustomFields($block->getType()) as $subField) {
                // Use the field UID to maintain uniqueness, as handles can be the same in Matrix/etc fields. This helps with diffing resolution.
                $fieldKey = $subField->handle . ':' . $subField->uid;

                $subValue = $block->getFieldValue($subField->handle);

                $serializedFieldValues[$fieldKey] = $fieldsService->serializeValue($subField, $block, $subValue);
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

            // Ensure that we track the owner of any existing (or new) block for inner fields (see relation fields)
            $existingBlock->owner = $element;
            $existingBlock->uid = $blockUid;

            $blockId = $existingBlock->id ?? 'new' . ++$new;

            $normalizedFieldValues = [];

            $blockTypeUid = $block['type'] ?? null;
            $blockType = $blockTypes[$blockTypeUid] ?? null;

            // Serialize all nested fields properly through Zen
            if ($blockType) {
                // Swap out the UID with the ID now it's been ported
                $block['type'] = $blockType->id;

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

}
