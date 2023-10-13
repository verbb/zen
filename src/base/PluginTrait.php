<?php
namespace verbb\zen\base;

use verbb\zen\Zen;
use verbb\zen\services\Elements;
use verbb\zen\services\Export;
use verbb\zen\services\Fields;
use verbb\zen\services\Import;
use verbb\zen\web\assets\app\ZenAsset;

use verbb\base\LogTrait;
use verbb\base\helpers\Plugin;

use nystudio107\pluginvite\services\VitePluginService;

trait PluginTrait
{
    // Properties
    // =========================================================================

    public static ?Zen $plugin = null;


    // Traits
    // =========================================================================

    use LogTrait;
    

    // Static Methods
    // =========================================================================

    public static function config(): array
    {
        Plugin::bootstrapPlugin('zen');

        return [
            'components' => [
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
            ],
        ];
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

}