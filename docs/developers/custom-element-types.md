# Custom Element Types

An element type class tells Zen how to export and import one kind of Craft element. Zen includes classes for Craft's own elements, and for Commerce, Navigation and Events elements when those plugins are installed. A custom element type lets Zen move elements from another plugin or module, or change how Zen handles an element it already supports.

## Create the Element Type Class

Extend `verbb\zen\base\Element`. You need to implement `elementType()` and `getExportOptions()`, and override the other methods where your element has its own attributes to move between installs.

The following class handles Craft's categories, which shows the shape of a class for any grouped element. Zen already includes a class for categories, so for your own element you'd return its class from `elementType()` and use its own groups.

```php
<?php
namespace modules\sitemodule\zen;

use Craft;
use craft\base\ElementInterface;
use craft\db\Table;
use craft\elements\Category as CategoryElement;
use craft\elements\db\ElementQueryInterface;
use craft\helpers\ArrayHelper;
use craft\helpers\Cp;
use craft\helpers\Db;
use verbb\zen\base\Element as ZenElement;
use verbb\zen\models\ImportFieldTab;

class CategoryElementType extends ZenElement
{
    public static function elementType(): string
    {
        return CategoryElement::class;
    }

    public static function exportKeyForElement(ElementInterface $element): array
    {
        return ['group' => $element->group->handle];
    }

    public static function getExportOptions(ElementQueryInterface $query): array|bool
    {
        $options = [];

        foreach (Craft::$app->getCategories()->getAllGroups() as $group) {
            $options[] = [
                'label' => $group->name,
                'criteria' => ['group' => $group->handle],
                'count' => (clone $query)->group($group)->count(),
            ];
        }

        return $options;
    }

    public static function defineSerializedElement(ElementInterface $element, array $data): array
    {
        // IDs differ between installs, so export the group's UID instead
        $data['groupUid'] = Db::uidById(Table::CATEGORYGROUPS, $element->groupId);

        return $data;
    }

    public static function defineNormalizedElement(array $data): array
    {
        // Swap the exported UID back to this install's group ID
        $data['groupId'] = Db::idByUid(Table::CATEGORYGROUPS, ArrayHelper::remove($data, 'groupUid'));

        return $data;
    }

    public static function defineImportTableAttributes(): array
    {
        return [
            'group' => Craft::t('zen', 'Group'),
        ];
    }

    public static function defineImportTableValues(?ElementInterface $newElement, ?ElementInterface $currentElement, ?string $state): array
    {
        $element = $newElement ?? $currentElement;

        if (!$element) {
            return [];
        }

        return [
            'group' => $element->group->name,
        ];
    }

    public static function defineImportFieldTabs(ElementInterface $element, string $type): array
    {
        return [
            new ImportFieldTab([
                'name' => Craft::t('zen', 'Meta'),
                'fields' => [
                    'slug' => Cp::textFieldHtml([
                        'label' => Craft::t('app', 'Slug'),
                        'id' => 'slug',
                        'value' => $element->slug,
                        'disabled' => true,
                    ]),
                    'enabled' => Cp::lightswitchFieldHtml([
                        'label' => Craft::t('app', 'Enabled'),
                        'id' => 'enabled',
                        'on' => $element->enabled,
                        'disabled' => true,
                    ]),
                ],
            ]),
        ];
    }
}
```

## Register the Element Type

Zen finds element types through the `registerElementTypes` event. Register your class from your module or plugin’s `init()` method.

```php
use craft\events\RegisterComponentTypesEvent;
use modules\sitemodule\zen\CategoryElementType;
use verbb\zen\services\Elements;
use yii\base\Event;

Event::on(Elements::class, Elements::EVENT_REGISTER_ELEMENT_TYPES, function(RegisterComponentTypesEvent $event) {
    $event->types[] = CategoryElementType::class;
});
```

To replace one of Zen's own classes, remove it from `$event->types` and add yours.

## Methods

::: reference
### `elementType()`

**Returns:** `string`

Returns the class name of the Craft element this class handles, such as `craft\elements\Category`. Every element type class must implement this.
:::

::: reference
### `getExportOptions()`

**Arguments:** `$query` (`ElementQueryInterface`), an element query for the export's date range.

**Returns:** `array|bool`

Returns the groups of elements someone can pick on the export screen. Each item has a `label`, the `criteria` (element query parameters) that fetch that group, a `count` of matching elements, and optionally `children` in the same format. Every element type class must implement this.
:::

::: reference
### `exportKeyForElement()`

**Arguments:** `$element` (`ElementInterface`)

**Returns:** `array`

Returns the element query parameters that identify the export group this element belongs to, matching the `criteria` from `getExportOptions()`. Zen stores this when it records an element change, so the change shows under the right group on the export screen.
:::

::: reference
### `defineSerializedElement()`

**Arguments:** `$element` (`ElementInterface`), `$data` (`array`), the data Zen has already serialized.

**Returns:** `array`

Returns the exported data for an element, with your element's own attributes added to `$data`. Swap any IDs for UIDs or handles, because IDs differ between installs. Always return `$data` with your changes, as the default implementation returns an empty array and would discard everything Zen serialized.
:::

::: reference
### `defineNormalizedElement()`

**Arguments:** `$data` (`array`), the exported data for one element.

**Returns:** `array`

Returns the element attributes to create the element with on import. This reverses `defineSerializedElement()`, such as swapping a UID back to this install's ID.
:::

::: reference
### `defineImportTableAttributes()`

**Returns:** `array`

Returns the extra columns to show in the import configuration table, as column keys and labels. Zen adds its own element, site, state and summary columns around them.
:::

::: reference
### `defineImportTableValues()`

**Arguments:** `$newElement` (`ElementInterface|null`), the element from the import. `$currentElement` (`ElementInterface|null`), the matching element already on this install. `$state` (`string|null`), the kind of change.

**Returns:** `array`

Returns the values for the columns from `defineImportTableAttributes()`, keyed the same way. Either element can be `null`, such as `$currentElement` for a new element.
:::

::: reference
### `defineImportFieldTabs()`

**Arguments:** `$element` (`ElementInterface`), `$type` (`string`), either `old` or `new` for the side of the comparison being shown.

**Returns:** `array`

Returns the extra tabs to show in the import preview, as `ImportFieldTab` models with a `name` and `fields` keyed by attribute, each field's value being its HTML. Use a "Meta" tab to show attributes specific to your element.
:::

::: reference
### `defineEagerLoadingMap()`

**Returns:** `array`

Returns the extra relations to eager-load when Zen fetches these elements for export or import, such as `['author']`. Zen already eager-loads parents, ancestors and custom fields.
:::

::: reference
### `elementUniqueIdentifier()`

**Returns:** `string`

Returns the attribute Zen uses to match an imported element with one on this install. The default is `uid`.
:::

::: reference
### `getElementForPreview()`

**Arguments:** `$element` (`ElementInterface`), `$type` (`string`), either `old` or `new`.

**Returns:** `void`

Prepares an element before Zen renders it in the import preview, such as setting attributes the preview form needs.
:::

::: reference
### `beforeImport()`

**Arguments:** `$importAction` (`ElementImportAction`)

**Returns:** `bool`

Returns whether the element should be imported. Call `parent::beforeImport()` when you override it, so field types and the [`beforeImport` event](events.md#the-beforeimport-event) still run.
:::

::: reference
### `afterImport()`

**Arguments:** `$importAction` (`ElementImportAction`)

**Returns:** `void`

Runs after an element is imported. Call `parent::afterImport()` when you override it, so field types and the [`afterImport` event](events.md#the-afterimport-event) still run.
:::
