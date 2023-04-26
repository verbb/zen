# Events
Zen provides a collection of events for extending its functionality. Modules and plugins can register event listeners, typically in their `init()` methods, to modify Zen’s behavior.


## Element Events
The below events are examples using the `Entry` class, but any class that inherits from the `verbb\zen\base\Element` class can use these events.

### The `modifyImportFieldTabs` event
The event that is triggered to modify the tabs and fields shown as the preview for the import configure table.

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

### The `modifyImportTableAttributes` event
The event that is triggered to modify the table attributes (the header columns) for the import configure table.

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

### The `modifyImportTableValues` event
The event that is triggered to modify the table values (the row columns) for the import configure table.

```php
use verbb\zen\elements\Entry;
use verbb\zen\events\ModifyElementImportTableAttributesEvent;
use yii\base\Event;

Event::on(Entry::class, Entry::EVENT_MODIFY_IMPORT_TABLE_VALUES, function(ModifyElementImportTableValuesEvent $event) {
    $elementType = $event->elementType;
    $values = $event->values;
    $diffs = $event->diffs;
    $old = $event->compare['old'];
    $new = $event->compare['new'];
    // ...
});
```

### The `modifyNormalizedData` event
The event that is triggered when a serialized element is normalized for import.

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

### The `modifySerializedData` event
The event that is triggered when an element is serialized for export.

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

### The `beforeImport` event
The event that is triggered before an element is imported.

```php
use verbb\zen\elements\Entry;
use verbb\zen\events\ElementImportEvent;
use yii\base\Event;

Event::on(Entry::class, Entry::EVENT_BEFORE_IMPORT, function(ElementImportEvent $event) {
    $importAction = $event->importAction;
    // ...
});
```

### The `afterImport` event
The event that is triggered after an element is imported.

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
The below events are examples using the `Matrix` class, but any class that inherits from the `verbb\zen\base\Field` class can use these events.

### The `beforeElementImport` event
The event that is triggered before an element is imported.

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

### The `afterElementImport` event
The event that is triggered after an element is imported.

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
