<?php
namespace verbb\zen\base;

use verbb\zen\Zen;
use verbb\zen\queue\jobs\RunImport;

use Craft;
use craft\base\ElementInterface;
use craft\helpers\Json;
use craft\helpers\Queue;

use Throwable;

trait ProcessingLogTrait
{
    // Static Methods
    // =========================================================================

    public static function resetProcessingLog(string $taskId): void
    {
        Craft::$app->getCache()->set('zen-process-log:' . $taskId, []);
    }

    public static function addProcessingLog(string $taskId, array $payload): void
    {
        $data = Craft::$app->getCache()->get('zen-process-log:' . $taskId) ?? [];
        $data[] = $payload;

        Craft::$app->getCache()->set('zen-process-log:' . $taskId, $data);
    }

    public static function getProcessingLog(string $taskId): array
    {
        return Craft::$app->getCache()->get('zen-process-log:' . $taskId) ?: [];
    }

    public static function getLogLabel(ElementInterface $element, array &$labels = [])
    {
        $labels[] = $element->getUiLabel();

        if ($parent = $element->getParent()) {
            self::getLogLabel($parent, $labels);
        }

        return implode(' > ', array_reverse($labels));
    }

    public static function getQueueJobByTaskId(string $taskId)
    {
        $queue = Craft::$app->getQueue();

        foreach ($queue->getJobInfo() as $jobInfoSummary) {
            try {
                $jobInfo = $queue->getJobDetails($jobInfoSummary['id']);
                $job = $jobInfo['job'] ?? null;

                if ($job instanceof RunImport && $job->taskId === $taskId) {
                    return $jobInfoSummary;
                }
            } catch (Throwable $e) {
                Zen::error(Craft::t('zen', 'Unable to fetch job info: “{message}” {file}:{line}', [
                    'message' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                ]));
            }
        }

        return null;
    }

    public static function createOrRestartImportJob(array $config)
    {
        $queue = Craft::$app->getQueue();

        if ($jobInfo = Zen::getQueueJobByTaskId($config['taskId'])) {
            $queue->retry($jobInfo['id']);
        } else {
            Queue::push(new RunImport($config));
        }
    }

}