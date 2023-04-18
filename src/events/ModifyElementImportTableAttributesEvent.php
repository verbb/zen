<?php
namespace verbb\zen\events;

use yii\base\Event;

class ModifyElementImportTableAttributesEvent extends Event
{
    // Properties
    // =========================================================================

    public string $elementType;
    public array $attributes = [];
}
