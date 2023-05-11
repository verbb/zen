<?php
namespace verbb\zen\elements;

use verbb\zen\base\Element as ZenElement;
use verbb\zen\helpers\Db;
use verbb\zen\models\ImportFieldTab;

use Craft;
use craft\base\ElementInterface;
use craft\elements\GlobalSet as GlobalSetElement;
use craft\elements\db\ElementQueryInterface;
use craft\helpers\Cp;

class GlobalSet extends ZenElement
{
    // Static Methods
    // =========================================================================

    public static function elementType(): string
    {
        return GlobalSetElement::class;
    }

    public static function exportKeyForElement(ElementInterface $element): array
    {
        return ['handle' => $element->handle];
    }

    public static function getExportOptions(ElementQueryInterface $query): array|bool
    {
        $options = [];

        foreach (Craft::$app->getGlobals()->getAllSets() as $globalSet) {
            $options[] = [
                'label' => $globalSet->name,
                'criteria' => ['handle' => $globalSet->handle],
                'count' => 1,
            ];
        }

        return $options;
    }

    public static function defineSerializedElement(ElementInterface $element, array $data): array
    {
        // Serialize any additional attributes. Be sure to switch out IDs for UIDs.
        $data['name'] = $element->name;
        $data['handle'] = $element->handle;

        // We want to remove `dateCreated` as it's not really applicable to Global Sets
        unset($data['dateCreated']);

        return $data;
    }

    public static function defineNormalizedElement(array $data): array
    {
        // Special-case to fetch the same field layout which isn't carried over
        $currentElement = static::elementType()::find()->uid($data['uid'])->one();

        if ($currentElement) {
            $data['fieldLayout'] = $currentElement->getFieldLayout();
        }

        return $data;
    }

    public static function defineImportTableAttributes(): array
    {
        return [
            'set' => Craft::t('zen', 'Global Set'),
        ];
    }

    public static function defineImportTableValues(array $diffs, ?ElementInterface $newElement, ?ElementInterface $currentElement, ?string $state): array
    {
        // Use either the new or current element to get data for, at this generic stage.
        $element = $newElement ?? $currentElement ?? null;

        if (!$element) {
            return [];
        }

        return [
            'set' => $element->name,
        ];
    }

    public static function defineImportFieldTabs(ElementInterface $element, string $type): array
    {
        return [
            new ImportFieldTab([
                'name' => Craft::t('zen', 'Meta'),
                'fields' => [
                    'name' => Cp::textFieldHtml([
                        'label' => Craft::t('app', 'Name'),
                        'id' => 'name',
                        'value' => $element->name,
                        'disabled' => true,
                    ]),
                    'handle' => Cp::textFieldHtml([
                        'label' => Craft::t('app', 'Handle'),
                        'id' => 'handle',
                        'value' => $element->handle,
                        'disabled' => true,
                    ]),
                ],
            ]),
        ];
    }

    public static function generateCompareHtml(?ElementInterface $element, array $diffs, string $type): string
    {
        // Special-case to fetch the same field layout which isn't carried over
        if ($type == 'new') {
            $currentElement = static::elementType()::find()->uid($element->uid)->one();

            if ($currentElement) {
                $element->setFieldLayout($currentElement->getFieldLayout());
            }
        }

        return parent::generateCompareHtml($element, $diffs, $type);
    }

}
