<?php
namespace verbb\zen\fields;

use verbb\zen\Zen;
use verbb\zen\base\Field as ZenField;
use verbb\zen\helpers\ArrayHelper;
use verbb\zen\models\ElementDiffer;

use Craft;
use craft\base\ElementInterface;
use craft\base\ElementQueryInterface;
use craft\base\FieldInterface;
use craft\elements\Entry;
use craft\fields\Matrix as MatrixField;

class Matrix extends BlockField
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

        // Convert ElementCollection to ElementQuery
        if (!($value instanceof ElementQueryInterface)) {
            $value = Entry::find()
                ->ownerId($element->id)
                ->fieldId($field->id)
                ->siteId($element->siteId);
        }

        foreach ($value->status(null)->all() as $block) {
            if ($registeredElement = Zen::$plugin->getElements()->getElementByType(Entry::class)) {
                $blocks[] = $registeredElement::getSerializedElement($block);
            }
        }

        return $blocks;
    }

    public static function normalizeValue(FieldInterface $field, ElementInterface $element, mixed $value): mixed
    {
        if (!is_array($value)) {
            return $value;
        }

        // Either find the existing block via UID, or set it as new
        $blocks = [];
        $new = 0;

        $entryTypes = ArrayHelper::index($field->getEntryTypes(), 'uid');
        $fieldsService = Zen::$plugin->getFields();

        foreach ($value as $block) {
            $blockUid = $block['uid'] ?? null;

            if ($blockUid) {
                $existingBlock = Entry::find()->uid($blockUid)->status(null)->one() ?? new Entry();
            } else {
                $existingBlock = new Entry();
            }

            // Ensure that we track the owner of any existing (or new) block for inner fields (see relation fields)
            $existingBlock->owner = $element;
            $existingBlock->uid = $blockUid;

            $blockId = $existingBlock->id ?? 'new' . ++$new;

            $normalizedFieldValues = [];

            $entryTypeUid = $block['typeUid'] ?? null;
            $entryType = $entryTypes[$entryTypeUid] ?? null;

            // Serialize all nested fields properly through Zen
            if ($entryType) {
                // Swap out the UID with the ID now it's been ported
                $block['type'] = $entryType->handle;

                foreach ($fieldsService->getCustomFields($entryType) as $subField) {
                    $subValue = $block['fields'][$subField->handle] ?? null;

                    $normalizedFieldValues[$subField->handle] = $fieldsService->normalizeValue($subField, $existingBlock, $subValue);
                }
            }

            $block['title'] = $block['title'] ?? null;
            $block['slug'] = $block['slug'] ?? null;
            $block['fields'] = $normalizedFieldValues;

            $blocks[$blockId] = $block;
        }

        return $blocks;
    }

    public static function getFieldForPreview(FieldInterface $field, ElementInterface $element, string $type): void
    {
        // Force Matrix to show in Blocks Mode for preview, other modes don't work so well.
        $field->viewMode = 'blocks';
    }

    public static function handleValueForDiff(FieldInterface $field, mixed &$oldValue, mixed &$newValue): ?array
    {
        $diffs = parent::handleValueForDiff($field, $oldValue, $newValue);

        // Remove some attributes that don't mean much to Matrix blocks, now they are Entries
        foreach ($diffs as $key => $diff) {
            unset($diffs[$key]['postDate'], $diffs[$key]['expiryDate'], $diffs[$key]['dateCreated'], $diffs[$key]['dateUpdated']);
        }

        return $diffs;
    }

}
