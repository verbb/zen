<?php
namespace verbb\zen\fields;

use verbb\zen\Zen;
use verbb\zen\base\Field as ZenField;

use Craft;
use craft\base\ElementInterface;
use craft\base\FieldInterface;

use benf\neo\elements\Block as NeoBlock;
use benf\neo\Field as NeoField;

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

        foreach ($value->all() as $block) {
            $blockId = $block->uid ?? 'new' . ++$new;

            $blocks[$blockId] = [
                'type' => $block->getType()->handle,
                'enabled' => $block->enabled,
                'collapsed' => $block->getCollapsed(),
                'level' => $block->level,
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
            $foundBlock = NeoBlock::find()->uid($blockUid)->status(null)->one();
            $blockId = $foundBlock->id ?? 'new' . ++$new;

            $blocks[$blockId] = $block;
        }

        return $blocks;
    }

}
