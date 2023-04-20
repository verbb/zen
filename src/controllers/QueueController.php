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

        foreach ($queue->getJobInfo() as $jobInfoSummary) {
            try {
                $jobInfo = $queue->getJobDetails($jobInfoSummary['id']);
                $job = $jobInfo['job'] ?? null;

                if ($job instanceof RunImport && $job->taskId === $taskId) {
                    $queue->release($jobInfoSummary['id']);
                }
            } catch (Throwable $e) {
                Zen::error(Craft::t('zen', 'Unable to fetch job info: “{message}” {file}:{line}', [
                    'message' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                ]));
            }
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

        // Extra check to return just the job task that we're after
        foreach ($queue->getJobInfo() as $jobInfoSummary) {
            try {
                $jobInfo = $queue->getJobDetails($jobInfoSummary['id']);
                $job = $jobInfo['job'] ?? null;

                if ($job instanceof RunImport && $job->taskId === $taskId) {
                    // Using `toArray()` isn't good enough here, but we want to add our own attributes
                    $zenJob = Json::decode(Json::encode($jobInfo));

                    // Add in custom properties
                    $zenJob['processingLog'] = Zen::getProcessingLog($taskId);

                    break;
                }
            } catch (Throwable $e) {
                Zen::error(Craft::t('zen', 'Unable to fetch job info: “{message}” {file}:{line}', [
                    'message' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                ]));
            }
        }

        return $this->asJson([
            'job' => $zenJob,
        ]);
    }
}
