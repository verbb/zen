# Events

Zen provides events for extending its functionality. Modules and plugins can register event listeners, typically in their `init()` methods, to change Zen’s behaviour.

## Element Events

These examples use Zen's `Entry` class, so they only run for entries. Listen on another of Zen's element type classes, such as `verbb\zen\elements\Category`, for that element, or on `verbb\zen\base\Element` for every element type.

### The `modifyImportFieldTabs` Event

The event that is triggered when Zen builds the extra tabs shown in the import preview for an element. Change `$event->tabs` to add, remove or reorder tabs.

```php
use verbb\zen\elements\Entry;
use verbb\zen\events\ModifyElementImportFieldTabsEvent;
use yii\base\Event;

Event::on(Entry::class, Entry::EVENT_MODIFY_IMPORT_FIELD_TABS, function(ModifyElementImportFieldTabsEvent $event) {
    $elementType = $event->elementType;
    $tabs = $event->tabs;
    // ...
});
```

### The `modifyImportTableAttributes` Event

The event that is triggered when Zen builds the columns of the import configuration table. Change `$event->attributes`, an array of column keys and labels, to add or remove columns.

```php
use verbb\zen\elements\Entry;
use verbb\zen\events\ModifyElementImportTableAttributesEvent;
use yii\base\Event;

Event::on(Entry::class, Entry::EVENT_MODIFY_IMPORT_TABLE_ATTRIBUTES, function(ModifyElementImportTableAttributesEvent $event) {
    $elementType = $event->elementType;
    $attributes = $event->attributes;
    // ...
});
```

### The `modifyImportTableValues` Event

The event that is triggered when Zen builds a row of the import configuration table. Change `$event->values`, keyed by column, to set the values for any columns you've added.

```php
use verbb\zen\elements\Entry;
use verbb\zen\events\ModifyElementImportTableValuesEvent;
use yii\base\Event;

Event::on(Entry::class, Entry::EVENT_MODIFY_IMPORT_TABLE_VALUES, function(ModifyElementImportTableValuesEvent $event) {
    $elementType = $event->elementType;
    $values = $event->values;
    // ...
});
```

### The `modifyNormalizedData` Event

The event that is triggered when Zen turns an element's exported data back into an element for import. Change `$event->values` to change the element's attributes, or `$event->fields` to change its custom field values, before Zen creates the element.

```php
use verbb\zen\elements\Entry;
use verbb\zen\events\ModifyElementNormalizedDataEvent;
use yii\base\Event;

Event::on(Entry::class, Entry::EVENT_MODIFY_NORMALIZED_DATA, function(ModifyElementNormalizedDataEvent $event) {
    $elementType = $event->elementType;
    $fields = $event->fields;
    $values = $event->values;
    // ...
});
```

### The `modifySerializedData` Event

The event that is triggered when Zen serializes an element for export. Change `$event->values` to change the exported data. Zen also serializes elements on the destination install to compare them, so make the same change on both installs.

```php
use verbb\zen\elements\Entry;
use verbb\zen\events\ModifyElementSerializedDataEvent;
use yii\base\Event;

Event::on(Entry::class, Entry::EVENT_MODIFY_SERIALIZED_DATA, function(ModifyElementSerializedDataEvent $event) {
    $elementType = $event->elementType;
    $element = $event->element;
    $values = $event->values;
    // ...
});
```

### The `beforeImport` Event

The event that is triggered before an element is imported. Set `$event->isValid` to `false` to stop the element being imported. The import then fails with an error at that element, and the elements after it aren't imported.

```php
use verbb\zen\elements\Entry;
use verbb\zen\events\ElementImportEvent;
use yii\base\Event;

Event::on(Entry::class, Entry::EVENT_BEFORE_IMPORT, function(ElementImportEvent $event) {
    $importAction = $event->importAction;
    // ...
});
```

### The `afterImport` Event

The event that is triggered after an element is imported successfully.

```php
use verbb\zen\elements\Entry;
use verbb\zen\events\ElementImportEvent;
use yii\base\Event;

Event::on(Entry::class, Entry::EVENT_AFTER_IMPORT, function(ElementImportEvent $event) {
    $importAction = $event->importAction;
    // ...
});
```

## Field Events

These examples use Zen's `Matrix` class, so they only run for Matrix fields. Listen on another of Zen's field type classes for that field, or on `verbb\zen\base\Field` for every field, including fields Zen handles without a class of their own.

### The `beforeElementImport` Event

The event that is triggered for each field in an element's field layout, before the element is imported. Set `$event->isValid` to `false` to stop the element being imported. The import then fails with an error at that element, and the elements after it aren't imported.

```php
use verbb\zen\events\ElementFieldImportEvent;
use verbb\zen\fields\Matrix;
use yii\base\Event;

Event::on(Matrix::class, Matrix::EVENT_BEFORE_ELEMENT_IMPORT, function(ElementFieldImportEvent $event) {
    $field = $event->field;
    $element = $event->element;
    // ...
});
```

### The `afterElementImport` Event

The event that is triggered for each field in an element's field layout, after the element is imported successfully.

```php
use verbb\zen\events\ElementFieldImportEvent;
use verbb\zen\fields\Matrix;
use yii\base\Event;

Event::on(Matrix::class, Matrix::EVENT_AFTER_ELEMENT_IMPORT, function(ElementFieldImportEvent $event) {
    $field = $event->field;
    $element = $event->element;
    // ...
});
```

## Registration Events

### The `registerElementTypes` Event

The event that is triggered when Zen collects the element types it can export and import. See [Custom Element Types](custom-element-types.md).

```php
use craft\events\RegisterComponentTypesEvent;
use verbb\zen\services\Elements;
use yii\base\Event;

Event::on(Elements::class, Elements::EVENT_REGISTER_ELEMENT_TYPES, function(RegisterComponentTypesEvent $event) {
    $event->types[] = CategoryElementType::class;
});
```

### The `registerFieldTypes` Event

The event that is triggered when Zen collects the field types it handles values for. See [Custom Field Types](custom-field-types.md).

```php
use craft\events\RegisterComponentTypesEvent;
use verbb\zen\services\Fields;
use yii\base\Event;

Event::on(Fields::class, Fields::EVENT_REGISTER_FIELD_TYPES, function(RegisterComponentTypesEvent $event) {
    $event->types[] = FeaturedEntryFieldType::class;
});
```
