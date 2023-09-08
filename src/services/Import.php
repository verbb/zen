<?php
namespace verbb\zen\services;

use verbb\zen\Zen;
use verbb\zen\helpers\ArrayHelper;
use verbb\zen\helpers\DiffHelper;
use verbb\zen\models\ElementImportAction;
use verbb\zen\models\ElementImportDependency;

use Craft;
use craft\base\Component;
use craft\base\ElementInterface;
use craft\helpers\FileHelper;
use craft\helpers\Json;
use craft\helpers\StringHelper;

use Closure;
use Exception;

class Import extends Component
{
    // Static Methods
    // =========================================================================

    public static function createDependency(array $sourceData, array $destinationData, Closure $callback): void
    {
        $elementType = $sourceData['type'] ?? null;

        // Ensure that the dependency is Zen-enabled
        if ($registeredElement = Zen::$plugin->getElements()->getElementByType($elementType)) {
            $elementIdentifier = $registeredElement::elementUniqueIdentifier();
            $elementUid = $sourceData[$elementIdentifier] ?? null;
            $dependencyKey = $destinationData[$elementIdentifier] ?? null;

            if ($dependencyKey && $elementUid) {
                // The linked-to element isn't imported yet, so add that as a dependency
                Zen::$plugin->getImport()->addImportDependency($dependencyKey, new ElementImportDependency([
                    'elementImportAction' => new ElementImportAction([
                        'elementType' => $registeredElement,
                        'action' => ElementImportAction::ACTION_SAVE,
                        'data' => $sourceData,
                        'element' => $registeredElement::getNormalizedElement($sourceData, true),
                    ]),
                    'data' => [
                        'sourceData' => $sourceData,
                        'destinationData' => $destinationData,
                    ],
                    'callback' => $callback,
                ]));
            }
        }
    }


    // Properties
    // =========================================================================
    
    private array $_dependencies = [];
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

        $differ = new \verbb\zen\models\ElementDiffer();

        // Eager-load any fields automatically. Called here outside of the loop for performance
        $eagerLoadingFieldsMap = Zen::$plugin->getFields()->getEagerLoadingMap();

        foreach ($data as $elementType => $dataItems) {
            $newItems = [];

            // Consolidate all modified/deleted/restored elements
            foreach ($dataItems as $stateKey => $dataElements) {
                foreach ($dataElements as $dataItem) {
                    // Append what sort of action this will be
                    $dataItem['state'] = $stateKey;

                    $newItems[] = $dataItem;
                }
            }

            // Get all the UIDs in the provided import to query in one go for performance
            // But, not all elements use UID for their unique identifier (Users use email)
            $elementIdentifier = $elementType::elementUniqueIdentifier();
            $elementIdentifiers = array_keys(ArrayHelper::index($newItems, $elementIdentifier));

            // Re-index the new items with their unique UID + siteUID key
            $newItems = ArrayHelper::index($newItems, function($item) use ($elementIdentifier) {
                return $item[$elementIdentifier] . ':' . $item['siteUid'];
            });

            // Elements will define any eager-loaded attributes, along with us eager-loading fields automatically
            $eagerLoadingMap = array_merge($elementType::getEagerLoadingMap(), $eagerLoadingFieldsMap);

            // Do an element query to fetch all the items provided in the import for _this_ install. It's more performant to do
            // all at once, and we also want to get any trashed elements in case we're restoring.
            $elements = $elementType::elementType()::find()
                ->$elementIdentifier($elementIdentifiers)
                ->status(null)
                ->trashed(null)
                ->with($eagerLoadingMap)
                ->siteId('*')
                ->all();

            // Re-index the elements with their unique UID + siteUID key
            $elements = ArrayHelper::index($elements, function($element) use ($elementIdentifier) {
                return $element->$elementIdentifier . ':' . $element->site->uid;
            });

            $oldItems = [];

            foreach ($elements as $elementKey => $element) {
                // Ensure we serialize the old (current) element the same way we serialize the new exported element for accurate compare
                $oldItems[$elementKey] = $elementType::getSerializedElement($element);
            }

            $elementData = [];

            foreach ($newItems as $newItemKey => $newItem) {
                // Store the state for what action needs to be done when importing (save/delete/restore the element)
                // along with showing what state the action is in a summary (add/change/delete/remove).
                $elementActionState = 'save';
                $elementState = null;
                $diffs = [];
                $itemState = ArrayHelper::remove($newItem, 'state');

                // Something might have gone wrong, so exit
                if (!$itemState) {
                    continue;
                }

                // Find an existing element
                $currentElement = $elements[$newItemKey] ?? null;

                // If modified (add or change), run diff checks
                if ($itemState === 'modified') {
                    // Find the same (serialized) element on this install. If not found, it's new
                    $oldItem = $oldItems[$newItemKey] ?? [];

                    if ($oldItem) {
                        // Run a diff check on the two serialized elements
                        $diffs = $differ->doDiff($oldItem, $newItem);

                        if ($diffs) {
                            // Apply the patch of the diff to the origin element
                            $newItem = $differ->applyDiff($oldItem, $diffs);

                            $elementState = 'change';
                        } else {
                            // A destination element exists, but no diffs found, so no need to action.
                            continue;
                        }
                    } else {
                        $elementState = 'add';
                    }
                }

                // Add the ID into the source item from the destination item - if it exists. After we compare.
                if ($currentElement) {
                    $newItem['id'] = $currentElement->id;
                }

                // Do final setups for the new/current/actioned element
                if ($itemState === 'modified') {
                    $elementToAction = $elementType::getNormalizedElement($newItem, $returnElementData);
                    $newElement = $elementToAction;
                } else if ($itemState === 'deleted') {
                    $elementState = 'delete';
                    $elementActionState = 'delete';

                    $elementToAction = $currentElement;
                    $newElement = null;
                } else if ($itemState === 'restored') {
                    $elementState = 'restore';
                    $elementActionState = 'restore';

                    $currentElement = null;
                    $elementToAction = $elementType::getNormalizedElement($newItem, $returnElementData);
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
                        'data' => $newItem,
                        'element' => $elementToAction,
                    ]);
                } else {
                    // Generate data used for the "row" of the table for this element compare. We can't send models to Vue.
                    $summaryCount = $differ->getSummaryCount($diffs);
                    $tableData = $elementType::getImportTableValues($summaryCount, $newElement, $currentElement, $elementState);

                    if ($tableData) {
                        $elementData[] = $tableData;
                
                        // Increment our summary for a nice look. Added here to ensure there are no errors for the row
                        $summary[$elementState] += 1;
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

    public function getImportPreview(string $id, array $data): array
    {
        $oldHtml = '';
        $newHtml = '';

        $differ = new \verbb\zen\models\ElementDiffer();

        $elementType = null;
        $itemState = null;
        $newItem = [];

        // Find just the source data we need for this element/site
        foreach ($data as $type => $dataItems) {
            $elementIdentifier = $type::elementUniqueIdentifier();

            foreach ($dataItems as $stateKey => $dataElements) {
                foreach ($dataElements as $dataItem) {
                    $elementId = $dataItem[$elementIdentifier] . ':' . $dataItem['siteUid'];

                    if ($elementId === $id) {
                        $elementType = $type;
                        $itemState = $stateKey;
                        $newItem = $dataItem;

                        break 3;
                    }
                }
            }
        }

        if ($newItem) {
            $elementIdentifier = $elementType::elementUniqueIdentifier();
            $elementIdentifiers = $newItem[$elementIdentifier] ?? null;

            // Fetch the element on this site
            $currentElement = $elementType::find()
                ->$elementIdentifier($elementIdentifiers)
                ->status(null)
                ->trashed(null)
                ->siteId('*')
                ->one();

            // Create a serialized version to compare with
            $oldItem = $currentElement ? $elementType::getSerializedElement($currentElement) : [];

            $diffs = [];

            // If modified (add or change), run diff checks
            if ($itemState === 'modified') {
                if ($oldItem) {
                    $diffs = $differ->doDiff($oldItem, $newItem);

                    if ($diffs) {
                        // Apply the patch of the diff to the origin element
                        $newItem = $differ->applyDiff($oldItem, $diffs);
                    }
                } else {
                    // This is just for show more than anything. Because this is all new info, there will be a bunch
                    // of attributes to add, but not all are shown visually to the user. If we used the diff data, 
                    // this would show more new items to apply that you can see, which is confusing. Instead, 
                    // construct "fake" diffs (all add) just for the fields and meta fields for the element.
                    $elementToAction = $elementType::getNormalizedElement($newItem, true);

                    if ($tempDiffs = $differ->doDiff($oldItem, $newItem)) {
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

            if ($itemState === 'modified') {
                $newElement = $elementType::getNormalizedElement($newItem, true);
            } else if ($itemState === 'deleted') {
                $newElement = $currentElement;
            } else if ($itemState === 'restored') {
                $newElement = $elementType::getNormalizedElement($newItem, true);
            } else {
                $newElement = null;
            }

            // Generate the old/new summary of attributes and fields
            $diffSummary = $differ->getSummaryFieldIndicators($diffs);

            $oldHtml = $elementType::generateCompareHtml($currentElement, $diffSummary, 'old');
            $newHtml = $elementType::generateCompareHtml($newElement, $diffSummary, 'new');
        }

        return [
            'old' => $oldHtml,
            'new' => $newHtml,
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

    public function addImportDependency(string $identifier, ElementImportDependency $dependency): void
    {
        $this->_dependencies[$identifier][] = $dependency;
    }

    public function getImportDependencies(string $identifier): array
    {
        return $this->_dependencies[$identifier] ?? [];
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

        $this->runPostImport();
    }

    public function runPostImport(): void
    {
        // For any items in a structure, these will have been imported at the end. Re-order them here, now that
        // all the siblings are in place. It's too difficult to tackle this on each step as siblings could be
        // being imported too alongside the element.
        $this->moveItemsInStructure();
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

        // Are there any dependencies on the element (does it contain a reference to another element?)
        // This shouid be imported first, and then a callback fired to notify the original element
        foreach ($this->getImportDependencies($element->$elementIdentifier) as $dependency) {
            $dependency->process($element);
        }

        // Do a final check to see if the element has already been imported by something else in the import.
        $elementType::checkExistingImportedElement($importAction);

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
    
    public function moveItemsInStructure(): void
    {
        $structuresService = Craft::$app->getStructures();
        $elementsService = Craft::$app->getElements();

        // Structure element/sibling relationships are stored when normalizing each element
        foreach (Zen::$plugin->getElements()->getStructureItems() as $uid => $siblingInfo) {
            $siteId = $siblingInfo['siteId'] ?? null;
            $elementType = $siblingInfo['elementType'] ?? null;

            // Get both the element and sibling from UID
            $element = $elementsService->getElementByUid($uid, $elementType, $siteId);

            // Most of the time, we only care about the previous sibling to add after, but the only case where
            // it's the first item and there's no previous sibling, we use the next sibling as a reference.
            if ($element && $element->structureId) {
                $prevSiblingUid = $siblingInfo['prevSibling'] ?? null;
                $nextSiblingUid = $siblingInfo['nextSibling'] ?? null;

                if ($prevSiblingUid) {
                    $prevSibling = $elementsService->getElementByUid($prevSiblingUid, $elementType, $siteId);

                    if ($prevSibling) {
                        $structuresService->moveAfter($element->structureId, $element, $prevSibling);
                    }
                } else if ($nextSiblingUid) {
                    $nextSibling = $elementsService->getElementByUid($nextSiblingUid, $elementType, $siteId);

                    if ($nextSibling) {
                        $structuresService->moveBefore($element->structureId, $element, $nextSibling);
                    }
                }
            }
        }
    }

}
