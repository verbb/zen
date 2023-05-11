<?php
namespace verbb\zen\services;

use verbb\zen\Zen;
use verbb\zen\helpers\ArrayHelper;

use craft\base\Component;
use craft\helpers\Db;
use craft\helpers\Json;

use DateTime;

class Export extends Component
{
    // Properties
    // =========================================================================
    
    private array $_storedFiles = [];


    // Public Methods
    // =========================================================================
    
    public function getExportOptions(DateTime $fromDate, DateTime $toDate): array
    {
        $options = [];

        foreach (Zen::$plugin->getElements()->getAllElementTypes() as $elementType) {
            $fromDate->setTime(0, 0, 0);
            $toDate->setTime(23, 59, 59);

            $query = $elementType::find()
                ->dateUpdated(['and', '>= ' . Db::prepareDateForDb($fromDate), '< ' . Db::prepareDateForDb($toDate)])
                ->siteId('*');

            $elements = $elementType::getExportOptions($query);

            if ($elements !== false) {
                // For each item, add a prefix to the `value` param to allow us to record what type of element it's for.
                $this->_decorateElementOptionValues($elementType::exportKey(), $elements);

                $options[] = [
                    'label' => $elementType::pluralDisplayName(),
                    'value' => $elementType::pluralLowerDisplayName(),
                    'count' => array_sum(ArrayHelper::getColumn($elements, 'count')),
                    'children' => $elements,
                ];
            }
        }

        $options = [[
            'label' => 'All elements',
            'value' => '*',
            'count' => array_sum(ArrayHelper::getColumn($options, 'count')),
            'children' => $options,
        ]];

        // Automatically add a `level` and `checked` option to each item
        $this->_decorateOptions($options);

        return $options;
    }

    public function getExportData(array $elements, DateTime $fromDate, DateTime $toDate): array
    {
        $json = [];

        $elementsService = Zen::$plugin->getElements();

        $fromDate->setTime(0, 0, 0);
        $toDate->setTime(23, 59, 59);

        // Index registered element types by their element type export key
        $registeredElementTypes = [];

        foreach (Zen::$plugin->getElements()->getAllElementTypes() as $elementType) {
            $registeredElementTypes[$elementType::exportKey()] = $elementType;
        }

        // Eager-load any fields automatically. Called here outside of the loop for performance
        $eagerLoadingFieldsMap = Zen::$plugin->getFields()->getEagerLoadingMap();

        foreach ($elements as $elementCriteria) {
            $elementCriteria = Json::decode($elementCriteria);
            $elementTypeKey = array_keys($elementCriteria)[0] ?? null;
            $elementType = $registeredElementTypes[$elementTypeKey] ?? null;

            // Check if there are any matching selected elements for this type in our chosen-to-export values
            if ($elementType) {
                $params = $elementCriteria[$elementTypeKey] ?? null;

                // Prepare an element query with the date range populated
                $dateRange = [Db::prepareDateForDb($fromDate), Db::prepareDateForDb($toDate)];

                // Elements will define any eager-loaded attributes, along with us eager-loading fields automatically
                $eagerLoadingMap = array_merge($elementType::getEagerLoadingMap(), $eagerLoadingFieldsMap);

                $query = $elementType::find()
                    ->dateUpdated(['and', '>= ' . $dateRange[0], '< ' . $dateRange[1]])
                    ->with($eagerLoadingMap)
                    ->siteId('*');

                // Get the raw elements for the type. Element classes will determine how to fetch the elements from the params
                foreach ($elementType::getExportData($query, $params) as $element) {
                    // Process the raw elements in an export-friendly way, ready for import
                    $json[$elementType]['modified'][] = $elementType::getSerializedElement($element);
                }

                // Also need to do a separate call for deleted/restored elements and store separately
                $json[$elementType]['deleted'] = $elementsService->getDeletedElementsForExport($elementType::elementType(), $dateRange, $elementCriteria);
                $json[$elementType]['restored'] = $elementsService->getRestoredElementsForExport($elementType::elementType(), $dateRange, $elementCriteria);
            }
        }

        return $json;
    }

    public function storeExportFile(array $payload): void
    {
        $this->_storedFiles[] = $payload;
    }

    public function getStoredExportFiles(): array
    {
        return $this->_storedFiles;
    }


    // Private Methods
    // =========================================================================

    private function _decorateOptions(&$options, $depth = 1): void
    {
        foreach ($options as &$option) {
            $option['level'] = $depth;

            if (isset($option['children'])) {
                $this->_decorateOptions($option['children'], $depth + 1);
            } else {
                $option['checked'] = true;
            }
        }
    }

    private function _decorateElementOptionValues($key, &$options): void
    {
        foreach ($options as &$option) {
            $criteria = $option['criteria'] ?? null;
            $value = $criteria ? [$key => $criteria] : $key;
            $option['value'] = Json::encode($value);

            if (isset($option['children'])) {
                $this->_decorateElementOptionValues($key, $option['children']);
            }
        }
    }
}
