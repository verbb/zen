<?php
namespace verbb\zen\fields;

use verbb\zen\base\Field as ZenField;

use Craft;
use craft\base\ElementInterface;
use craft\base\FieldInterface;

use besteadfast\preparsefield\fields\PreparseFieldType;

class Preparse extends ZenField
{
    // Static Methods
    // =========================================================================

    public static function fieldType(): string
    {
        return PreparseFieldType::class;
    }

    public static function serializeValue(FieldInterface $field, ElementInterface $element, mixed $value): mixed
    {
        return $value;
    }

    public static function normalizeValue(FieldInterface $field, ElementInterface $element, mixed $value): mixed
    {
        return $value;
    }

}
