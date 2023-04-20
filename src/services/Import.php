<?php
namespace verbb\zen\services;

use verbb\zen\Zen;
use verbb\zen\helpers\ArrayHelper;
use verbb\zen\models\ElementImportAction;

use Craft;
use craft\base\Component;
use craft\base\ElementInterface;
use craft\helpers\FileHelper;
use craft\helpers\Json;
use craft\helpers\StringHelper;

use Exception;

use Diff\Differ\MapDiffer;
use Diff\DiffOp\Diff\Diff;
use Diff\Patcher\MapPatcher;

class Import extends Component
{
    // Properties
    // =========================================================================
    
    private array $_storedFiles = [];


    // Public Methods
    // =========================================================================

    public function getImportConfiguration(array $data, bool $returnElementData = false): array
    {
        $config = [];

        $summary = [
            'add' => 0,
            'change' => 0,
            'delete' => 0,
            'restore' => 0,
        ];

        $differ = new MapDiffer(true);
        $patcher = new MapPatcher();

        foreach ($data as $elementType => $dataItems) {
            $sourceItems = [];

            // Consolidate all modified/deleted/restored elements
            foreach ($dataItems as $stateKey => $dataElements) {
                foreach ($dataElements as $dataItem) {
                    // Append what sort of action this will be
                    $dataItem['state'] = $stateKey;

                    $sourceItems[] = $dataItem;
                }
            }

            // Get all the UIDs in the provided import to query in one go for performance
            // But, not all elements use UID for their unique identifier (Users use email)
            $elementIdentifier = $elementType::elementUniqueIdentifier();
            $elementIdentifiers = array_keys(ArrayHelper::index($sourceItems, $elementIdentifier));

            // Re-index the soure items with their unique UID + siteUID key
            $sourceItems = ArrayHelper::index($sourceItems, function($item) use ($elementIdentifier) {
                return $item[$elementIdentifier] . ':' . $item['siteUid'];
            });

            // Do an element query to fetch all the items provided in the import for _this_ install. It's more performant to do
            // all at once, and we also want to get any trashed elements in case we're restoring.
            $elements = $elementType::elementType()::find()
                ->$elementIdentifier($elementIdentifiers)
                ->status(null)
                ->trashed(null)
                ->siteId('*')
                ->all();

            // Re-index the elements with their unique UID + siteUID key
            $elements = ArrayHelper::index($elements, function($element) use ($elementIdentifier) {
                return $element->$elementIdentifier . ':' . $element->site->uid;
            });

            $destItems = [];

            foreach ($elements as $elementKey => $element) {
                // Ensure we serialize the destination element the same way we serialize the source exported element for accurate compare
                $destItems[$elementKey] = $elementType::getSerializedElement($element);
            }

            $elementData = [];

            foreach ($sourceItems as $sourceKeyItem => $sourceItem) {
                $diffs = [];

                // Store the state for what action needs to be done when importing (save/delete/restore the element)
                // along with showing what state the action is in a summary (add/change/delete/remove).
                $elementActionState = 'save';
                $summaryState = null;
                $sourceItemState = ArrayHelper::remove($sourceItem, 'state');

                // Something might have gone wrong, so exit
                if (!$sourceItemState) {
                    continue;
                }

                // If modified (add or change), run diff checks
                if ($sourceItemState === 'modified') {
                    // Find the same element on this install. If not found, it's new
                    $destItem = $destItems[$sourceKeyItem] ?? [];

                    // If a destination element is found, do a diff check. Otherwise, it's treated as a new element.
                    if ($destItem) {
                        $diffs = $differ->doDiff($destItem, $sourceItem);

                        if ($diffs) {
                            // Apply the patch of the diff to the origin element
                            $sourceItem = $patcher->patch($destItem, new Diff($diffs));

                            $summaryState = 'change';
                        } else {
                            // A destination element exists, but no diffs found, so no need to action.
                            continue;
                        }
                    } else {
                        $summaryState = 'add';

                        // This is just for show more than anything. Because this is all new info, there will be a bunch
                        // of attributes to add, but not all are shown visually to the user. If we used the diff data, 
                        // this would show more new items to apply that you can see, which is confusing. Instead, 
                        // construct "fake" diffs (all add) just for the fields and meta fields for the element.
                        $elementToAction = $elementType::getNormalizedElement($sourceItem);
                        $newElement = $elementToAction;

                        if ($tempDiffs = $differ->doDiff($destItem, $sourceItem)) {
                            $attrs = [
                                'title',
                                'fields',
                            ];

                            foreach ($elementType::defineImportFieldTabs($elementToAction, 'new') as $tab) {
                                $attrs = array_merge($attrs, array_keys($tab->fields));
                            }

                            foreach ($attrs as $attr) {
                                if ($diffData = ($tempDiffs[$attr] ?? null)) {
                                    $diffs[$attr] = $diffData;
                                }
                            }
                        }
                    }
                }

                // Now that we're done comparing, turn the imported data into a proper element. 
                // This will have any changes already patched in - if there's an existing element on this install.
                $currentElement = $elements[$sourceKeyItem] ?? null;

                // Add the ID into the source item from the destination item - if it exists. After we compare.
                if ($currentElement) {
                    $sourceItem['id'] = $currentElement->id;
                }

                // Do final setups for the new/current/actioned element
                if ($sourceItemState === 'modified') {
                    $elementToAction = $elementType::getNormalizedElement($sourceItem);
                    $newElement = $elementToAction;
                } else if ($sourceItemState === 'deleted') {
                    $summaryState = 'delete';
                    $elementActionState = 'delete';

                    $elementToAction = $currentElement;
                    $newElement = null;
                } else if ($sourceItemState === 'restored') {
                    $summaryState = 'restore';
                    $elementActionState = 'restore';

                    $currentElement = null;
                    $elementToAction = $elementType::getNormalizedElement($sourceItem);
                    $newElement = $elementToAction;
                } else {
                    $elementToAction = null;
                    $newElement = null;
                }

                if ($returnElementData) {
                    // For when actually running the import, return instructions on what to do with the element
                    $elementData[] = new ElementImportAction([
                        'elementType' => $elementType,
                        'action' => $elementActionState,
                        'data' => $sourceItem,
                        'element' => $elementToAction,
                    ]);
                } else {
                    // Generate data used for the "row" of the table for this element compare. We can't send models to Vue.
                    $tableData = $elementType::getImportTableValues($diffs, $newElement, $currentElement, $summaryState);

                    if ($tableData) {
                        $elementData[] = $tableData;
                
                        // Increment our summary for a nice look. Added here to ensure there are no errors for the row
                        $summary[$summaryState] += 1;
                    }
                }
            }

            if ($elementData) {
                $config[] = [
                    'label' => $elementType::pluralDisplayName(),
                    'value' => StringHelper::toCamelCase($elementType::pluralLowerDisplayName()),
                    'columns' => $elementType::getImportTableAttributes(),
                    'rows' => $elementData,
                ];
            }
        }

        if ($returnElementData) {
            return $config;
        }

        return [
            'summary' => $summary,
            'elementData' => $config,
        ];
    }

    public function storeImportFile(array $payload): void
    {
        $this->_storedFiles[] = $payload;
    }

    public function getStoredImportFiles(): array
    {
        return $this->_storedFiles;
    }

    public function getImportPayload(string $filename): array
    {
        $payloadPath = Craft::$app->getPath()->getTempPath() . DIRECTORY_SEPARATOR . basename($filename, '.zip');
        $content = [];

        foreach (FileHelper::findFiles($payloadPath) as $file) {
            $filename = basename($file);

            if ($filename === 'content.json') {
                $content = Json::decode(file_get_contents($file));
            } else {
                $this->storeImportFile([
                    'filename' => $filename,
                    'path' => $file,
                    'content' => file_get_contents($file),
                ]);
            }
        }

        return $content;
    }

    public function runImport(string $filename, array $elementsToExclude = []): void
    {
        foreach ($this->getElementsToImport($filename) as $elementImportAction) {
            $success = $this->runElementAction($elementImportAction);

            if (!$success) {
                throw new Exception(Craft::t('zen', 'Failed: {type}:{error}', [
                    'type' => $elementImportAction->elementType,
                    'error' => Json::encode($elementImportAction->element->getErrors()),
                ]));
            }
        }
    }

    public function runElementAction(ElementImportAction $importAction): bool
    {
        $result = true;
        $element = $importAction->element;
        $elementType = $importAction->elementType;
        $elementIdentifier = $elementType::elementUniqueIdentifier();

        // Allow element type classes to modify
        if (!$elementType::beforeImport($importAction)) {
            return false;
        }

        // Because we can have both elements being imported and relation fields that create elements if they don't exist, 
        // processing can fall over itself. For example, importing "Entry 1" with a categories field with "Category 1", which is also
        // being imported at the same time as an element (maybe before, maybe after) will cause a ruckus. Instead, do a final check here
        // if this is a new element (no ID), and if an existing element is already found (check the UID) and patch in that ID.
        if (!$element->id && $element->$elementIdentifier) {
            $importedElement = $elementType::elementType()::find()
                ->$elementIdentifier($element->$elementIdentifier)
                ->siteId($element->siteId)
                ->status(null)
                ->trashed(null)
                ->one();

            if ($importedElement) {
                $element->id = $importedElement->id;
            }
        }

        if ($importAction->action === ElementImportAction::ACTION_SAVE) {
            $result = Craft::$app->getElements()->saveElement($element);
        }

        if ($importAction->action === ElementImportAction::ACTION_DELETE) {
            $result = Craft::$app->getElements()->deleteElement($element);
        }

        if ($importAction->action === ElementImportAction::ACTION_RESTORE) {
            // Restoring is a little different. Try and find a trashed element, but if it doesn't exist, create it new
            if ($element->id) {
                $result = Craft::$app->getElements()->restoreElement($element);
            } else {
                $result = Craft::$app->getElements()->saveElement($element);
            }
        }

        if ($result) {
            $elementType::afterImport($importAction);
        }

        if (!$result) {
            Zen::error(Craft::t('zen', 'Unable to import {type}:{errors}', [
                'type' => $elementType,
                'errors' => Json::encode($element->getErrors()),
            ]));
        }

        return $result;
    }

    public function getElementsToImport(string $filename, array $elementsToExclude = []): array
    {
        $elementImportActions = [];

        // Fetch the content from the uploaded file (storing any extra files in cache)
        $json = Zen::$plugin->getImport()->getImportPayload($filename);

        // Get the configuration for the import, but this time return element import instructions, rather than summary info.
        // This just allows us to use the same function for generating configuration/preview/review and the actual import
        $elementData = Zen::$plugin->getImport()->getImportConfiguration($json, true);

        // Pluck just the elements, ensuring we exclude anything
        foreach ($elementData as $data) {
            foreach ($data['rows'] as $elementIndex => $elementImportAction) {
                $excludedIndexes = $elementsToExclude[$data['value']] ?? [];

                if (!in_array($elementIndex, $excludedIndexes)) {
                    $elementImportActions[] = $elementImportAction;
                }
            }
        }

        return $elementImportActions;
    }

}
