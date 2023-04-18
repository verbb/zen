<?php
namespace verbb\zen\records;

use craft\db\ActiveRecord;

class ElementAction extends ActiveRecord
{
    // Public Methods
    // =========================================================================

    public static function tableName(): string
    {
        return '{{%zen_elements}}';
    }
}
