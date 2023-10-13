<?php
namespace verbb\zen\helpers;

use verbb\zen\Zen;
use verbb\zen\web\assets\app\ZenAsset;

use verbb\base\helpers\Plugin as BasePlugin;

class Plugin extends BasePlugin
{
    // Static Methods
    // =========================================================================

    public static function registerAsset(string $path): void
    {
        $viteService = Zen::$plugin->getVite();

        $scriptOptions = [
            'depends' => [
                ZenAsset::class,
            ],
            'onload' => null,
        ];

        $styleOptions = [
            'depends' => [
                ZenAsset::class,
            ],
        ];

        $viteService->register($path, false, $scriptOptions, $styleOptions);

        // Provide nice build errors - only in dev
        if ($viteService->devServerRunning()) {
            $viteService->register('@vite/client', false);
        }
    }

}
