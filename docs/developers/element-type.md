# Element Type
You can register your own Element Type to tailor import/export behaviour for certain elements, or even extend an existing Element Type.

## Example
First, you'll need to get familiar with [creating a module](https://verbb.io/blog/everything-you-need-to-know-about-modules). In our example, we're going to create a module with the namespace set to `modules\zenmodule` and the module ID to `zen-module`.

Your main module class will need to register your custom class (which we're about to create). Add this to your `init()` method.

```php
namespace modules\zenmodule;

use craft\events\RegisterComponentTypesEvent;
use modules\zenmodule\CustomElement;
use verbb\zen\services\Elements;
use yii\base\Event;
use yii\base\Module;

class ZenModule extends Module
{
    // ...

    public function init()
    {
        parent::init();

        // ...

        Event::on(Elements::class, Elements::EVENT_REGISTER_ELEMENT_TYPES, function(RegisterComponentTypesEvent $event) {
            $event->types[] = CustomElement::class;
        });

        // ...
    }

    // ...
}
```

Create the following class to house your Element Type logic.

```php
<?php
namespace modules\zenmodule;

use verbb\zen\base\Element as ZenElement;
use verbb\zen\models\ImportFieldTab;

use Craft;
use craft\base\ElementInterface;
use craft\db\Table;
use craft\elements\Category as CategoryElement;
use craft\elements\db\ElementQueryInterface;
use craft\helpers\ArrayHelper;
use craft\helpers\Cp;
use craft\helpers\Db;

class CustomElement extends ZenElement
{
    public static function elementType(): string
    {
        // Replace this with the element `ElementInterface` that you wish to support
        return CategoryElement::class;
    }

    public static function exportKeyForElement(ElementInterface $element): array
    {
        // Add any element query params to be used when grouping elements for export
        return ['group' => $element->group->handle];
    }

    public static function getExportOptions(ElementQueryInterface $query): array|bool
    {
        $options = [];

        // Generate a collection of options for users to pick. These should relate to your own element groupings
        // like Groups for Categories, Sections/Entry Types for Entries, etc.
        foreach (Craft::$app->getCategories()->getAllGroups() as $group) {
            $options[] = [
                'label' => $group->name,
                'criteria' => ['group' => $group->handle],
                'count' => $query->group($group)->count(),
            ];
        }

        return $options;
    }

    public static function defineSerializedElement(ElementInterface $element, array $data): array
    {
        // When generating an element for export, we serialize it to have just what we need (and in the format we need)
        // so we can import it on another install. We're pretty restrictive about what content we do save, as we don't need
        // everything for an element. Importantly, any references to IDs should be swapped to UIDs or handles. This is because
        // on the destination install, the ID likely won't be the same (think `authorId`).

        // This is also called when comparing on the destination install, to ensure there's consistency.

        $data['groupUid'] = $element->getGroup()->uid;

        return $data;
    }

    public static function defineNormalizedElement(array $data): array
    {
        // And here, we are doing the reverse of `defineSerializedElement()`, where we're taking the serialized content from an export
        // and converting it back to the value that's appropriate on the destination install. For example, we might have a `groupId` setting
        // for an elment, but because the ID will change on each install, we store the UID. We want to convert that UID back to an ID.
        $data['groupId'] = Db::idByUid(Table::CATEGORYGROUPS, ArrayHelper::remove($data, 'groupUid'));

        return $data;
    }

    public static function defineImportTableAttributes(): array
    {
        // Return any columns to be shown in the configure table when importing.
        return [
            'group' => Craft::t('zen', 'Group'),
        ];
    }

    public static function defineImportTableValues(array $diffs, ?ElementInterface $newElement, ?ElementInterface $currentElement, ?string $state): array
    {
        // Return any columns to be shown in the configure table when importing.

        // Use either the new or current element to get data for, at this generic stage.
        $element = $newElement ?? $currentElement ?? null;

        if (!$element) {
            return [];
        }

        return [
            'group' => $element->group->name,
        ];
    }

    public static function defineImportFieldTabs(ElementInterface $element, string $type): array
    {
        // Return any tabs to be shown in the configure table when importing. At the very least, you'll want to include a "Meta" tab which
        // includes all the element attributes that are specific to this element type.
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
                    'dateCreated' => Cp::dateTimeFieldHtml([
                        'label' => Craft::t('app', 'Date Created'),
                        'id' => 'dateCreated',
                        'value' => $element->dateCreated,
                        'disabled' => true,
                    ]),
                ],
            ]),
        ];
    }
}
```
