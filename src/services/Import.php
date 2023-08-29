<?php
namespace verbb\zen\services;

use verbb\zen\Zen;
use verbb\zen\helpers\ArrayHelper;
use verbb\zen\helpers\DiffHelper;
use verbb\zen\models\ElementImportAction;
use verbb\zen\models\MapDiffer;

use Craft;
use craft\base\Component;
use craft\base\ElementInterface;
use craft\helpers\FileHelper;
use craft\helpers\Json;
use craft\helpers\StringHelper;

use Exception;

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

        // Eager-load any fields automatically. Called here outside of the loop for performance
        $eagerLoadingFieldsMap = Zen::$plugin->getFields()->getEagerLoadingMap();

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

            $destItems = [];

            foreach ($elements as $elementKey => $element) {
                // Ensure we serialize the destination element the same way we serialize the source exported element for accurate compare
                $destItems[$elementKey] = $elementType::getSerializedElement($element);
            }

            $elementData = [];

            foreach ($sourceItems as $sourceKeyItem => $sourceItem) {
                $diffSummary = [];

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
                        // Remove any parents, we don't want to use them in diffs
                        if (!$returnElementData) {
                            unset($sourceItem['parent'], $destItem['parent']);
                        }

                        // Save an instance of this source and destination data to determine a summary.
                        // We can only really report on this for changes, as adding new elements contains lots of
                        // extra data we don't want to report on as a summary.
                        $diffSummary = DiffHelper::getDiffSummary([$destItem, $sourceItem]);

                        // Get diffs between source and destination to be applied.
                        $diffs = $differ->doDiff($destItem, $sourceItem);

                        // We also check if there are _meaningful_ diffs. This is helpful because `doDiff` will recursively
                        // diff arrays, but we don't always want that to show as a change. For example, an element might contain
                        // an entry field and an entry in that field could have changes itself. We **don't** want that listed 
                        // as a change against the top-level element, because it technically isn't.
                        if ($diffs && $diffSummary) {
                            // Apply the patch of the diff to the origin element
                            $sourceItem = $patcher->patch($destItem, new Diff($diffs));

                            $summaryState = 'change';
                        } else {
                            // A destination element exists, but no diffs found, so no need to action.
                            continue;
                        }
                    } else {
                        $summaryState = 'add';
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
                    $elementToAction = $elementType::getNormalizedElement($sourceItem, $returnElementData);
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
                    $elementToAction = $elementType::getNormalizedElement($sourceItem, $returnElementData);
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
                    $tableData = $elementType::getImportTableValues($diffSummary, $newElement, $currentElement, $summaryState);

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

    public function getImportPreview(string $id, array $data): array
    {
        $oldHtml = '';
        $newHtml = '';

        $differ = new MapDiffer(true);
        $patcher = new MapPatcher();

        $elementType = null;
        $sourceItemState = null;
        $sourceItem = [];

        // Find just the source data we need for this element/site
        foreach ($data as $type => $dataItems) {
            $elementIdentifier = $type::elementUniqueIdentifier();

            foreach ($dataItems as $stateKey => $dataElements) {
                foreach ($dataElements as $dataItem) {
                    $elementId = $dataItem[$elementIdentifier] . ':' . $dataItem['siteUid'];

                    if ($elementId === $id) {
                        $elementType = $type;
                        $sourceItemState = $stateKey;
                        $sourceItem = $dataItem;

                        break 3;
                    }
                }
            }
        }

        if ($sourceItem) {
            $elementIdentifier = $elementType::elementUniqueIdentifier();
            $elementIdentifiers = $sourceItem[$elementIdentifier] ?? null;

            // Fetch the element on this site
            $currentElement = $elementType::find()
                ->$elementIdentifier($elementIdentifiers)
                ->status(null)
                ->trashed(null)
                ->siteId('*')
                ->one();

            // Create a serialized version to compare with
            $destItem = $currentElement ? $elementType::getSerializedElement($currentElement) : [];

            $diffs = [];

            // Remove any parents, we don't want to use them in diffs
            unset($sourceItem['parent'], $destItem['parent']);

            // If modified (add or change), run diff checks
            if ($sourceItemState === 'modified') {
                if ($destItem) {
                    $diffs = $differ->doDiff($destItem, $sourceItem);

                    if ($diffs) {
                        // Apply the patch of the diff to the origin element
                        $sourceItem = $patcher->patch($destItem, new Diff($diffs));
                    }
                } else {
                    // This is just for show more than anything. Because this is all new info, there will be a bunch
                    // of attributes to add, but not all are shown visually to the user. If we used the diff data, 
                    // this would show more new items to apply that you can see, which is confusing. Instead, 
                    // construct "fake" diffs (all add) just for the fields and meta fields for the element.
                    $elementToAction = $elementType::getNormalizedElement($sourceItem, true);

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

            if ($sourceItemState === 'modified') {
                $newElement = $elementType::getNormalizedElement($sourceItem, true);
            } else if ($sourceItemState === 'deleted') {
                $newElement = $currentElement;
            } else if ($sourceItemState === 'restored') {
                $newElement = $elementType::getNormalizedElement($sourceItem, true);
            } else {
                $newElement = null;
            }

            // Generate the old/new summary of attributes and fields
            $oldHtml = $elementType::generateCompareHtml($currentElement, $diffs, 'old');
            $newHtml = $elementType::generateCompareHtml($newElement, $diffs, 'new');
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

        // We should always process the top-most parent first, then downward to this one
        if ($parent = $element->getParent()) {
            $parentElementAction = new ElementImportAction([
                'elementType' => $importAction->elementType,
                'action' => $importAction->action,
                'data' => $importAction->data,
                'element' => $parent,
            ]);

            $this->runElementAction($parentElementAction);
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

}
