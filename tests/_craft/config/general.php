<?php
use craft\helpers\App;

return [
    'devMode' => true,
    'omitScriptNameInUrls' => true,
    'cpTrigger' => 'admin',
    'securityKey' => App::env('SECURITY_KEY'),
];