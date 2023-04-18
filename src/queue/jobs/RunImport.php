<?php
namespace verbb\zen\queue\jobs;

use verbb\zen\Zen;

use Craft;
use craft\i18n\Translation;
use craft\queue\BaseJob;
use craft\queue\QueueInterface;

use Exception;

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

        foreach ($elementsToImport as $i => $elementImportAction) {
            $step = ($i + 1);

            $this->setProgress($queue, $step / $total, Translation::prep('app', 'Importing element {step, number} of {total, number}', [
                'step' => $step,
                'total' => $total,
            ]));

            $success = $importService->runElementAction($elementImportAction);

            if (!$success) {
                throw new Exception('Failed');
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
