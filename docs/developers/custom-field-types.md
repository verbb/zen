# Custom Field Types

A field type class tells Zen how to export and import the value of one kind of custom field. Zen includes classes for Matrix, and for Super Table, Neo, SEOmatic, Image Optimize, Preparse and Hyper fields when those plugins are installed, and handles relation fields and other fields that store simple values on its own. A custom field type is for a field whose value holds IDs, such as a field that links to entries, because IDs differ between installs.

## Create the Field Type Class

Extend `verbb\zen\base\Field` and implement `fieldType()`. Override `serializeValue()` and `normalizeValue()` to swap IDs for UIDs on export, and back again on import.

The following class handles a hypothetical `FeaturedEntryField`, whose value is an array with an `entryId` key.

```php
<?php
namespace modules\sitemodule\zen;

use craft\base\ElementInterface;
use craft\base\FieldInterface;
use craft\db\Table;
use craft\helpers\Db;
use modules\sitemodule\fields\FeaturedEntryField;
use verbb\zen\base\Field as ZenField;

class FeaturedEntryFieldType extends ZenField
{
    public static function fieldType(): string
    {
        return FeaturedEntryField::class;
    }

    public static function serializeValue(FieldInterface $field, ElementInterface $element, mixed $value): mixed
    {
        $value = $field->serializeValue($value, $element);

        // Export the entry's UID, as its ID will be different on the other install
        if (is_array($value) && !empty($value['entryId'])) {
            $value['entryUid'] = Db::uidById(Table::ELEMENTS, $value['entryId']);
            unset($value['entryId']);
        }

        return $value;
    }

    public static function normalizeValue(FieldInterface $field, ElementInterface $element, mixed $value): mixed
    {
        // Swap the UID back to this install's entry ID
        if (is_array($value) && !empty($value['entryUid'])) {
            $value['entryId'] = Db::idByUid(Table::ELEMENTS, $value['entryUid']);
            unset($value['entryUid']);
        }

        return $value;
    }
}
```

## Register the Field Type

Zen finds field types through the `registerFieldTypes` event. Register your class from your module or plugin’s `init()` method.

```php
use craft\events\RegisterComponentTypesEvent;
use modules\sitemodule\zen\FeaturedEntryFieldType;
use verbb\zen\services\Fields;
use yii\base\Event;

Event::on(Fields::class, Fields::EVENT_REGISTER_FIELD_TYPES, function(RegisterComponentTypesEvent $event) {
    $event->types[] = FeaturedEntryFieldType::class;
});
```

## Methods

::: reference
### `fieldType()`

**Returns:** `string`

Returns the class name of the Craft field this class handles. Every field type class must implement this.
:::

::: reference
### `serializeValue()`

**Arguments:** `$field` (`FieldInterface`), `$element` (`ElementInterface`), the element being exported. `$value` (`mixed`), the field's value.

**Returns:** `mixed`

Returns the field's value as it should be exported. The default calls the field's own `serializeValue()`.
:::

::: reference
### `normalizeValue()`

**Arguments:** `$field` (`FieldInterface`), `$element` (`ElementInterface`), the element being imported. `$value` (`mixed`), the exported value.

**Returns:** `mixed`

Returns the value to set on the field when importing. The default returns the value unchanged, and Craft normalises it when the element's field values are set.
:::

::: reference
### `isSupported()`

**Returns:** `bool`

Returns whether Zen can move this field's values. Zen leaves unsupported fields out of exports, imports and the import preview. The default is `true`.
:::

::: reference
### `handleValueForDiff()`

**Arguments:** `$field` (`FieldInterface`), `$oldValue` (`mixed`), `$newValue` (`mixed`), both passed by reference.

**Returns:** `array|null`

Returns the differences between the field's current and imported values, for fields that need their own comparison, such as Matrix comparing each nested entry. Return `null` to let Zen compare the values, after any changes you've made to `$oldValue` and `$newValue`.
:::

::: reference
### `getFieldForPreview()`

**Arguments:** `$field` (`FieldInterface`), `$element` (`ElementInterface`), `$type` (`string`), either `old` or `new`.

**Returns:** `void`

Prepares the field's value on an element before Zen renders it in the import preview.
:::

::: reference
### `beforeElementImport()`

**Arguments:** `$field` (`FieldInterface`), `$element` (`ElementInterface`)

**Returns:** `bool`

Returns whether the element containing this field should be imported. Call `parent::beforeElementImport()` when you override it, so the [`beforeElementImport` event](events.md#the-beforeelementimport-event) still runs.
:::

::: reference
### `afterElementImport()`

**Arguments:** `$field` (`FieldInterface`), `$element` (`ElementInterface`)

**Returns:** `void`

Runs after the element containing this field is imported. Call `parent::afterElementImport()` when you override it, so the [`afterElementImport` event](events.md#the-afterelementimport-event) still runs.
:::
