<?php
namespace verbb\zen\helpers;

use verbb\zen\Zen;
use verbb\zen\web\assets\app\ZenAsset;

use Craft;

class Plugin
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

    public static function isPluginInstalledAndEnabled(string $plugin): bool
    {
        $pluginsService = Craft::$app->getPlugins();

        // Ensure that we check if initialized, installed and enabled. 
        // The plugin might be installed but disabled, or installed and enabled, but missing plugin files.
        return $pluginsService->isPluginInstalled($plugin) && $pluginsService->isPluginEnabled($plugin) && $pluginsService->getPlugin($plugin);
    }

}
