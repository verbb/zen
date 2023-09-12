<?php
namespace verbb\zen\models;

use craft\base\Model;

use Mistralys\Diff\Diff as DiffText;

class Diff extends Model
{
    // Properties
    // =========================================================================

    public mixed $oldValue = null;
    public mixed $newValue = null;


    // Public Methods
    // =========================================================================

    public function getDiffHtml()
    {
        if (is_string($this->oldValue) && is_string($this->newValue)) {
            $diff = DiffText::compareStrings($this->oldValue, $this->newValue);
            
            return $diff->toHTML();
        }
    }

}
