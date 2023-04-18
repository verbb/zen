<?php
namespace verbb\zen\events;

use yii\base\Event;

class ModifyElementNormalizedDataEvent extends Event
{
    // Properties
    // =========================================================================

    public string $elementType;
    public array $fields = [];
    public array $values = [];
}
