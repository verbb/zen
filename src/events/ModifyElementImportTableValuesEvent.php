<?php
namespace verbb\zen\events;

use yii\base\Event;

class ModifyElementImportTableValuesEvent extends Event
{
    // Properties
    // =========================================================================

    public string $elementType;
    public array $values = [];
}
