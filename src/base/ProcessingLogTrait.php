<?php
namespace verbb\zen\base;

use Craft;
use craft\base\ElementInterface;

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

}