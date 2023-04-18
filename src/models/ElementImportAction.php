<?php
namespace verbb\zen\models;

use craft\base\ElementInterface;
use craft\base\Model;

class ElementImportAction extends Model
{
    // Constants
    // =========================================================================

    public const ACTION_SAVE = 'save';
    public const ACTION_DELETE = 'delete';
    public const ACTION_RESTORE = 'restore';


    // Properties
    // =========================================================================

    public string $elementType;
    public string $action;
    public ?array $data;
    public ElementInterface $element;

}
