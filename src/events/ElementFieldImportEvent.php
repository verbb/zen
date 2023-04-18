<?php
namespace verbb\zen\events;

use craft\base\ElementInterface;
use craft\base\FieldInterface;

use craft\events\ModelEvent;

class ElementFieldImportEvent extends ModelEvent
{
    // Properties
    // =========================================================================

    public FieldInterface $field;
    public ElementInterface $element;
}
