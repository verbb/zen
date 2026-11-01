<?php
namespace verbb\zen\controllers;

use verbb\zen\Zen;
use verbb\zen\helpers\Plugin;
use verbb\zen\models\Settings;

use Craft;
use craft\helpers\FileHelper;
use craft\helpers\Json;
use craft\web\Controller;

use yii\web\NotFoundHttpException;
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
        $this->requireCpRequest();
        $this->requirePermission('accessPlugin-zen');

        $tempPath = realpath(Craft::getAlias('@storage/runtime/temp'));
        $path = realpath(Craft::getAlias('@storage/runtime/temp/') . $this->request->getRequiredParam('path'));

        // Only serve files extracted from an import, resolving the path first so it can't lead anywhere else
        if (!$tempPath || !$path || !is_file($path) || !str_starts_with($path, $tempPath . DIRECTORY_SEPARATOR . 'zen-import-')) {
            throw new NotFoundHttpException('File not found.');
        }

        $mimeType = FileHelper::getMimeTypeByExtension($path);

        // These files come straight from an import, so don't let the browser run anything in them. Images are unaffected.
        $this->response->getHeaders()
            ->set('Content-Security-Policy', 'sandbox')
            ->set('X-Content-Type-Options', 'nosniff');

        return $this->response->sendFile($path, basename($path), [
            'mimeType' => $mimeType,
            'inline' => true,
        ]);
    }
}
