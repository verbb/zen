<?php
namespace verbb\zen\controllers;

use verbb\zen\Zen;

use Craft;
use craft\helpers\DateTimeHelper;
use craft\helpers\Json;
use craft\helpers\StringHelper;
use craft\web\Controller;

use yii\web\Response;

use Exception;

use ZipArchive;

class ExportController extends Controller
{
    // Public Methods
    // =========================================================================

    public function actionIndex(): ?Response
    {
        $fromDate = DateTimeHelper::toDateTime($this->request->getParam('fromDate'));
        $toDate = DateTimeHelper::toDateTime($this->request->getParam('toDate'));

        if (!$fromDate || !$toDate) {
            return $this->asFailure(Craft::t('zen', 'You must select a "From Date" and "To Date".'));
        }

        $elements = $this->request->getParam('elements');

        if (!$elements) {
            return $this->asFailure(Craft::t('zen', 'You must select some elements to export.'));
        }

        $json = Zen::$plugin->getExport()->getExportData($elements, $fromDate, $toDate);

        if (!$json) {
            return $this->asFailure(Craft::t('zen', 'No data available to export.'));
        }

        $zipPath = Craft::$app->getPath()->getTempPath() . '/' . StringHelper::UUID() . '.zip';
        $zip = new ZipArchive();

        if ($zip->open($zipPath, ZipArchive::CREATE) !== true) {
            throw new Exception('Cannot create zip at ' . $zipPath);
        }

        $zip->addFromString('content.json', Json::encode($json));

        // Some elements will define extra files to be saved in the export zip
        foreach (Zen::$plugin->getExport()->getStoredExportFiles() as $file) {
            $zip->addFromString($file['filename'], $file['content']);
        }

        $zip->close();

        return $this->response->sendFile($zipPath, 'zen-' . StringHelper::UUID() . '.zip');
    }

    public function actionGetElementOptions(): ?Response
    {
        $fromDate = DateTimeHelper::toDateTime($this->request->getParam('fromDate'));
        $toDate = DateTimeHelper::toDateTime($this->request->getParam('toDate'));

        if (!$fromDate || !$toDate) {
            return $this->asFailure(Craft::t('zen', 'You must select a "From Date" and "To Date".'));
        }

        $elementOptions = Zen::$plugin->getExport()->getExportOptions($fromDate, $toDate);

        return $this->asJson([
            'options' => $elementOptions,
        ]);
    }
}
