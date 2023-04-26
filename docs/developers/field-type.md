# Field Type
You can register your own Field Type to tailor import/export behaviour for certain fields, or even extend an existing Field Type.

## Example
First, you'll need to get familiar with [creating a module](https://verbb.io/blog/everything-you-need-to-know-about-modules). In our example, we're going to create a module with the namespace set to `modules\zenmodule` and the module ID to `zen-module`.

Your main module class will need to register your custom class (which we're about to create). Add this to your `init()` method.

```php
namespace modules\zenmodule;

use craft\events\RegisterComponentTypesEvent;
use modules\zenmodule\CustomField;
use verbb\zen\services\Fields;
use yii\base\Event;
use yii\base\Module;

class ZenModule extends Module
{
    // ...

    public function init()
    {
        parent::init();

        // ...

        Event::on(Fields::class, Fields::EVENT_REGISTER_FIELD_TYPES, function(RegisterComponentTypesEvent $event) {
            $event->types[] = CustomField::class;
        });

        // ...
    }

    // ...
}
```

Create the following class to house your Field Type logic.

```php
<?php
namespace modules\zenmodule;

use verbb\zen\base\Field as ZenField;

use craft\base\ElementInterface;
use craft\base\FieldInterface;
use craft\elements\MatrixBlock;
use craft\fields\Matrix as MatrixField;

class CustomField extends ZenField
{
    public static function fieldType(): string
    {
        // Replace this with the field `FieldInterface` that you wish to support
        return MatrixField::class;
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
```
