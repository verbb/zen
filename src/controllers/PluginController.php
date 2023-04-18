<?php
namespace verbb\zen\controllers;

use verbb\zen\Zen;
use verbb\zen\helpers\Plugin;
use verbb\zen\models\Settings;

use Craft;
use craft\helpers\FileHelper;
use craft\helpers\Json;
use craft\web\Controller;

use yii\web\Response;

class PluginController extends Controller
{
    // Public Methods
    // =========================================================================

    public function actionIndex(): Response
    {
        $view = Craft::$app->getView();

        Plugin::registerAsset('app/src/js/zen.js');

        // Get the base path for Vue router (`index.php/admin/zen` or `admin/zen`)
        $path = trim(explode('/zen', parse_url($this->request->absoluteUrl)['path'])[0], '/');

        $view->registerJs('new Craft.Zen.App(' . Json::encode([
            'basePath' => "$path/zen",
            'systemName' => Craft::$app->getSystemName(),
        ]) . ');');

        return $this->renderTemplate('zen');
    }

    public function actionSettings(): Response
    {
        /* @var Settings $settings */
        $settings = Zen::$plugin->getSettings();

        return $this->renderTemplate('zen/settings', [
            'settings' => $settings,
        ]);
    }

    public function actionTempAsset(): mixed
    {
        $path = trim($this->request->getParam('path'));

        // Strip out any `../` or `./` path changes to prevent skipping to other directories
        $path = preg_replace('/(\.\.\/)+/', '', $path);

        $path = Craft::getAlias('@storage/runtime/temp/' . $path);
        $mimeType = FileHelper::getMimeTypeByExtension($path);

        return $this->response->sendFile($path, basename($path), [
            'mimeType' => $mimeType,
            'inline' => true,
        ]);
    }
}
