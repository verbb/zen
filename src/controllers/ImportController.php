<?php
namespace verbb\zen\controllers;

use verbb\zen\Zen;
use verbb\zen\queue\jobs\RunImport;

use Craft;
use craft\helpers\FileHelper;
use craft\helpers\Json;
use craft\helpers\Queue;
use craft\web\Controller;
use craft\web\UploadedFile;

use yii\web\Response;

use ZipArchive;

class ImportController extends Controller
{
    // Public Methods
    // =========================================================================

    public function actionIndex(): ?Response
    {
        $uploadedFile = UploadedFile::getInstanceByName('file');

        if (!$uploadedFile) {
            return $this->asFailure(Craft::t('zen', 'You must upload a file.'));
        }

        $filename = 'zen-import-' . gmdate('ymd_His') . '.zip';
        $fileLocation = Craft::$app->getPath()->getTempPath() . DIRECTORY_SEPARATOR . $filename;

        move_uploaded_file($uploadedFile->tempName, $fileLocation);

        return $this->asJson([
            'filename' => $filename,
        ]);
    }

    public function actionGetConfigData(): ?Response
    {
        $filename = $this->request->getRequiredParam('filename');
        $path = Craft::$app->getPath()->getTempPath() . DIRECTORY_SEPARATOR . $filename;

        if (!file_exists($path)) {
            return $this->asFailure(Craft::t('zen', 'Invalid file.'));
        }

        $zip = new ZipArchive();

        if ($zip->open($path) !== true) {
            return $this->asFailure(Craft::t('zen', "Unable to open the zip file at $path."));
        }

        $tempDir = Craft::$app->getPath()->getTempPath() . DIRECTORY_SEPARATOR . basename($filename, '.zip');
        FileHelper::createDirectory($tempDir);
        $zip->extractTo($tempDir);
        $zip->close();

        // if (!file_exists($contentPath)) {
        //     return $this->asFailure(Craft::t('zen', 'Unable to find content in uploaded file.'));
        // }

        // Fetch the content from the uploaded file (storing any extra files in cache)
        $json = Zen::$plugin->getImport()->getImportPayload($filename);

        $variables = Zen::$plugin->getImport()->getImportConfiguration($json);
        $variables['filename'] = $filename;

        return $this->asJson($variables);
    }

    public function actionGetReviewData(): ?Response
    {
        $filename = $this->request->getRequiredParam('filename');

        // Fetch the content from the uploaded file (storing any extra files in cache)
        $json = Zen::$plugin->getImport()->getImportPayload($filename);

        $variables = Zen::$plugin->getImport()->getImportConfiguration($json);
        $variables['filename'] = $filename;

        // Remove any excluded items
        $elementsToExclude = $this->request->getParam('elementsToExclude');
        $elementsToExclude = Json::decode(base64_decode($elementsToExclude));

        foreach ($variables['elementData'] as $elementDataKey => $data) {
            $excludedElement = $elementsToExclude[$data['value']] ?? [];

            foreach ($excludedElement as $excludedElementIndex) {
                unset($variables['elementData'][$elementDataKey]['rows'][$excludedElementIndex]);
            }
        }

        return $this->asJson($variables);
    }

    public function actionRun(): ?Response
    {
        $filename = $this->request->getRequiredParam('filename');
        $elementsToExclude = $this->request->getParam('elementsToExclude');
        $taskId = $this->request->getParam('taskId');
        $direct = $this->request->getParam('direct');

        $elementsToExclude = Json::decode(base64_decode($elementsToExclude));

        if (!is_array($elementsToExclude)) {
            $elementsToExclude = [];
        }

        Craft::$app->getDb()->backup();

        if ($direct) {
            Zen::$plugin->getImport()->runImport($filename, $elementsToExclude);
        } else {
            Queue::push(new RunImport([
                'filename' => $filename,
                'elementsToExclude' => $elementsToExclude,
                'taskId' => $taskId,
            ]));
        }

        return $this->asJson(['success' => true]);
    }
}
