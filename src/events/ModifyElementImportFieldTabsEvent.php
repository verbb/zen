<?php
namespace verbb\zen\events;

use yii\base\Event;

class ModifyElementImportFieldTabsEvent extends Event
{
    // Properties
    // =========================================================================

    public string $elementType;
    public array $tabs = [];
}
