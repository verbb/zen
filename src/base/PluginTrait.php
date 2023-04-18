<?php
namespace verbb\zen\base;

use verbb\zen\Zen;
use verbb\zen\services\Elements;
use verbb\zen\services\Export;
use verbb\zen\services\Fields;
use verbb\zen\services\Import;
use verbb\zen\web\assets\app\ZenAsset;

use Craft;

use yii\log\Logger;

use verbb\base\BaseHelper;

use nystudio107\pluginvite\services\VitePluginService;

trait PluginTrait
{
    // Static Properties
    // =========================================================================

    public static Zen $plugin;


    // Public Methods
    // =========================================================================

    public static function log(string $message, array $attributes = []): void
    {
        if ($attributes) {
            $message = Craft::t('zen', $message, $attributes);
        }

        Craft::getLogger()->log($message, Logger::LEVEL_INFO, 'zen');
    }

    public static function error(string $message, array $attributes = []): void
    {
        if ($attributes) {
            $message = Craft::t('zen', $message, $attributes);
        }

        Craft::getLogger()->log($message, Logger::LEVEL_ERROR, 'zen');
    }


    // Public Methods
    // =========================================================================

    public function getElements(): Elements
    {
        return $this->get('elements');
    }

    public function getExport(): Export
    {
        return $this->get('export');
    }

    public function getFields(): Fields
    {
        return $this->get('fields');
    }

    public function getImport(): Import
    {
        return $this->get('import');
    }

    public function getVite(): VitePluginService
    {
        return $this->get('vite');
    }


    // Private Methods
    // =========================================================================

    private function _setPluginComponents(): void
    {
        $this->setComponents([
            'elements' => Elements::class,
            'export' => Export::class,
            'fields' => Fields::class,
            'import' => Import::class,
            'vite' => [
                'class' => VitePluginService::class,
                'assetClass' => ZenAsset::class,
                'useDevServer' => true,
                'devServerPublic' => 'http://localhost:4030/',
                'errorEntry' => 'js/main.js',
                'cacheKeySuffix' => '',
                'devServerInternal' => 'http://localhost:4030/',
                'checkDevServer' => true,
                'includeReactRefreshShim' => false,
            ],
        ]);

        BaseHelper::registerModule();
    }

    private function _setLogging(): void
    {
        BaseHelper::setFileLogging('zen');
    }

}