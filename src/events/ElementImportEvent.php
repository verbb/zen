<?php
namespace verbb\zen\events;

use verbb\zen\models\ElementImportAction;

use craft\events\ModelEvent;

class ElementImportEvent extends ModelEvent
{
    // Properties
    // =========================================================================

    public ElementImportAction $importAction;
}
