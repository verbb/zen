<?php
namespace verbb\zen\models;

use craft\base\FieldInterface;
use craft\base\Model;

class ZenField extends Model
{
    // Properties
    // =========================================================================

    public FieldInterface $field;
    public string $fieldType;

}
