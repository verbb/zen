<?php
namespace verbb\zen\variables;

use verbb\zen\Zen;

class ZenVariable
{
    // Public Methods
    // =========================================================================

    public function getPlugin(): Zen
    {
        return Zen::$plugin;
    }

    public function getPluginName(): string
    {
        return Zen::$plugin->getPluginName();
    }
    
}