<?php
namespace verbb\zen\base;

use verbb\zen\base\FieldInterface as ZenFieldInterface;
use verbb\zen\events\ElementFieldImportEvent;

use craft\base\ElementInterface;
use craft\base\FieldInterface;

use yii\base\Event;

abstract class Field implements ZenFieldInterface
{
    // Constants
    // =========================================================================

    public const EVENT_BEFORE_ELEMENT_IMPORT = 'beforeElementImport';
    public const EVENT_AFTER_ELEMENT_IMPORT = 'afterElementImport';


    // Static Methods
    // =========================================================================

    public static function serializeValue(FieldInterface $field, ElementInterface $element, mixed $value): mixed
    {
        return null;
    }

    public static function normalizeValue(FieldInterface $field, ElementInterface $element, mixed $value): mixed
    {
        return null;
    }

    public static function getFieldForPreview(FieldInterface $field, ElementInterface $element, string $type): void
    {
    }

    public static function beforeElementImport(FieldInterface $field, ElementInterface $element): bool
    {
        // Trigger a 'beforeImport' event
        $event = new ElementFieldImportEvent([
            'field' => $field,
            'element' => $element,
        ]);
        Event::trigger(static::class, self::EVENT_BEFORE_ELEMENT_IMPORT, $event);

        return $event->isValid;
    }

    public static function afterElementImport(FieldInterface $field, ElementInterface $element): void
    {
        // Trigger an 'afterImport' event
        Event::trigger(static::class, self::EVENT_AFTER_ELEMENT_IMPORT, new ElementFieldImportEvent([
            'field' => $field,
            'element' => $element,
        ]));
    }

}
