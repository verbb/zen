<?php
namespace verbb\zen\queue\jobs;

use verbb\zen\Zen;

use Craft;
use craft\db\Table;
use craft\helpers\Db;
use craft\helpers\Json;
use craft\i18n\Translation;
use craft\queue\BaseJob;

use Exception;
use Throwable;

class RunImport extends BaseJob
{
    // Properties
    // =========================================================================

    public string $taskId = '';
    public string $filename = '';
    public array $elementsToExclude = [];
   

    // Public Methods
    // =========================================================================

    public function execute($queue): void
    {
        $filename = $this->filename;
        $elementsToExclude = $this->elementsToExclude;

        $importService = Zen::$plugin->getImport();
        $elementsToImport = $importService->getElementsToImport($filename, $elementsToExclude);

        $total = count($elementsToImport);

        // Reset any logged for this task, just in case
        Zen::resetProcessingLog($this->taskId);

        foreach ($elementsToImport as $i => $elementImportAction) {
            // Catch errors so we can first log them for output, then throw as normal
            try {
                $step = ($i + 1);

                $this->setProgress($queue, $step / $total, Translation::prep('zen', 'Importing element {step, number} of {total, number}', [
                    'step' => $step,
                    'total' => $total,
                ]));

                $success = $importService->runElementAction($elementImportAction);

                if (!$success) {
                    $code = 0;
                    $errorMessage = Craft::t('zen', 'An unknown error occurred.');

                    if ($elementImportAction->element->getErrors()) {
                        // No need to show the trace info when there are element errors
                        $code = 1;
                        $errorMessage = Json::encode($elementImportAction->element->getErrors());
                    }

                    // Stop any further processing
                    throw new Exception($errorMessage, $code);
                } else {
                    Zen::addProcessingLog($this->taskId, [
                        'success' => true,
                        'element' => [
                            'type' => $elementImportAction->elementType::displayName(),
                            'label' => Zen::getLogLabel($elementImportAction->element),
                            'uid' => $elementImportAction->element->uid,
                        ],
                    ]);
                }
            } catch (Throwable $e) {
                // Store process log as a cache, a local property won't work
                Zen::addProcessingLog($this->taskId, [
                    'success' => false,
                    'error' => $e->getMessage(),
                    'trace' => $e->getCode() !== 1 ? $e->getTraceAsString() : null,
                    'element' => [
                        'type' => $elementImportAction->elementType::displayName(),
                        'label' => Zen::getLogLabel($elementImportAction->element),
                        'uid' => $elementImportAction->element->uid,
                    ],
                ]);

                throw new Exception($e);
            }
        }
    }

    // Protected Methods
    // =========================================================================

    protected function defaultDescription(): ?string
    {
        return Translation::prep('zen', 'Importing content via Zen.');
    }
}
