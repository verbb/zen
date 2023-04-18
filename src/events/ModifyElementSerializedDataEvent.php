<?php
namespace verbb\zen\events;

use craft\base\ElementInterface;

use yii\base\Event;

class ModifyElementSerializedDataEvent extends Event
{
    // Properties
    // =========================================================================

    public string $elementType;
    public ElementInterface $element;
    public array $values = [];
}
