<?php
namespace verbb\zen\models;

use craft\base\Model;

class Settings extends Model
{
    // Properties
    // =========================================================================

    public string $pluginName = 'Zen';
    public bool $stopOnError = true;


    // Protected Methods
    // =========================================================================

    protected function defineRules(): array
    {
        $rules = parent::defineRules();

        $rules[] = [['pluginName'], 'trim'];
        $rules[] = [['pluginName'], 'required'];

        return $rules;
    }
}
