# Configuration

You can customise Zen’s settings using a PHP configuration file. This is optional: each setting has a default, so you only need to include the values you want to change.

To override a setting, create `zen.php` in your Craft project’s `/config` directory and return an array of setting names and values. For example, the following will change the name displayed in the control panel:

```php
<?php

return [
    'pluginName' => 'Zen Tools',
];
```

All other settings keep their defaults. Add any further settings you want to change to the same array. The options below explain the available settings and their defaults.

## Configuration Options

::: reference
### `pluginName`

**Type:** `string` · **Default:** `'Zen'`

The name displayed for the plugin in the control panel.
:::


## Control Panel
You can also manage configuration settings through the Control Panel by visiting Settings → Zen.
