<?php
namespace verbb\zen\controllers;

use verbb\zen\Zen;
use verbb\zen\queue\jobs\RunImport;

use Craft;
use craft\helpers\Json;
use craft\web\Controller;

use yii\web\Response;

use Throwable;

class QueueController extends Controller
{
    // Public Methods
    // =========================================================================

    public function actionCancel(): Response
    {
        $this->requireAcceptsJson();
        $this->requirePostRequest();
        $this->requirePermission('utility:queue-manager');

        $queue = Craft::$app->getQueue();
        $taskId = $this->request->getRequiredParam('taskId');

        if ($jobInfo = Zen::getQueueJobByTaskId($taskId)) {
            $queue->release($jobInfo['id']);
        }

        return $this->asSuccess();
    }

    public function actionGetJobInfo(): Response
    {
        $this->requireAcceptsJson();
        $this->requirePermission('accessCp');

        $queue = Craft::$app->getQueue();
        $taskId = $this->request->getRequiredParam('taskId');
        $zenJob = null;

        if ($jobInfo = Zen::getQueueJobByTaskId($taskId)) {
            $jobInfo = $queue->getJobDetails($jobInfo['id']);
                    
            // Using `toArray()` isn't good enough here, but we want to add our own attributes
            $zenJob = Json::decode(Json::encode($jobInfo));

            // Add in custom properties
            $zenJob['processingLog'] = Zen::getProcessingLog($taskId);
        }

        return $this->asJson([
            'job' => $zenJob,
        ]);
    }
}
