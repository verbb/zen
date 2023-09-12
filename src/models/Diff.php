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

    public function getDiffHtml(): ?string
    {
        if (is_string($this->oldValue) && is_string($this->newValue)) {
            return DiffText::compareStrings($this->oldValue, $this->newValue)->toHTML();
        }

        return null;
    }

}
