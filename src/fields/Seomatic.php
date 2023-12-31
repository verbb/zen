<?php
namespace verbb\zen\fields;

use verbb\zen\base\Field as ZenField;

use Craft;
use craft\base\ElementInterface;
use craft\base\FieldInterface;

use nystudio107\seomatic\fields\SeoSettings;

class Seomatic extends ZenField
{
    // Static Methods
    // =========================================================================

    public static function fieldType(): string
    {
        return SeoSettings::class;
    }

    public static function isSupported(): bool
    {
        return false;
    }

    public static function serializeValue(FieldInterface $field, ElementInterface $element, mixed $value): mixed
    {
        return null;
    }

    public static function normalizeValue(FieldInterface $field, ElementInterface $element, mixed $value): mixed
    {
        return null;
    }

}
