<?php
namespace verbb\zen\events;

use craft\base\ElementInterface;

use yii\base\Event;

class ModifyElementImportTableAttributesEvent extends Event
{
    // Properties
    // =========================================================================

    public string $elementType;
    public array $attributes = [];
}
