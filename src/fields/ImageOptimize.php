<?php
namespace verbb\zen\fields;

use verbb\zen\base\Field as ZenField;

use Craft;
use craft\base\ElementInterface;
use craft\base\FieldInterface;

use nystudio107\imageoptimize\fields\OptimizedImages;

class ImageOptimize extends ZenField
{
    // Static Methods
    // =========================================================================

    public static function fieldType(): string
    {
        return OptimizedImages::class;
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
