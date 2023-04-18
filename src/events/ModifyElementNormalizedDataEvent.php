<?php
namespace verbb\zen\events;

use craft\base\ElementInterface;

use yii\base\Event;

class ModifyElementNormalizedDataEvent extends Event
{
    // Properties
    // =========================================================================

    public string $elementType;
    public array $fields = [];
    public array $values = [];
}
