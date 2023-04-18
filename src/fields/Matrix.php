<?php
namespace verbb\zen\fields;

use verbb\zen\Zen;
use verbb\zen\base\Field as ZenField;

use Craft;
use craft\base\ElementInterface;
use craft\base\FieldInterface;
use craft\helpers\Db;
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

        foreach ($value->all() as $block) {
            $blockId = $block->uid ?? 'new' . ++$new;

            $blocks[$blockId] = [
                'type' => $block->getType()->handle,
                'enabled' => $block->enabled,
                'collapsed' => $block->collapsed,
                'uid' => $block->uid,
                'fields' => $block->getSerializedFieldValues(),
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
