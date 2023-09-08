<?php
namespace verbb\zen\elements;

use verbb\zen\base\Element as ZenElement;
use verbb\zen\helpers\Db;
use verbb\zen\models\ImportFieldTab;

use Craft;
use craft\base\ElementInterface;
use craft\db\Table;
use craft\elements\Category as CategoryElement;
use craft\elements\db\ElementQueryInterface;
use craft\helpers\ArrayHelper;
use craft\helpers\Cp;

class Category extends ZenElement
{
    // Static Methods
    // =========================================================================

    public static function elementType(): string
    {
        return CategoryElement::class;
    }

    public static function exportKeyForElement(ElementInterface $element): array
    {
        return ['group' => $element->group->handle];
    }

    public static function getExportOptions(ElementQueryInterface $query): array|bool
    {
        $options = [];

        foreach (Craft::$app->getCategories()->getAllGroups() as $group) {
            $options[] = [
                'label' => $group->name,
                'criteria' => ['group' => $group->handle],
                'count' => $query->group($group)->count(),
            ];
        }

        return $options;
    }

    public static function defineSerializedElement(ElementInterface $element, array $data): array
    {
        // Serialize any additional attributes. Be sure to switch out IDs for UIDs.
        $data['groupUid'] = Db::uidById(Table::CATEGORYGROUPS, $element->groupId);

        return $data;
    }

    public static function defineNormalizedElement(array $data): array
    {
        $data['groupId'] = Db::idByUid(Table::CATEGORYGROUPS, ArrayHelper::remove($data, 'groupUid'));

        return $data;
    }

    public static function defineImportTableAttributes(): array
    {
        return [
            'group' => Craft::t('zen', 'Group'),
        ];
    }

    public static function defineImportTableValues(?ElementInterface $newElement, ?ElementInterface $currentElement, ?string $state): array
    {
        // Use either the new or current element to get data for, at this generic stage.
        $element = $newElement ?? $currentElement ?? null;

        if (!$element) {
            return [];
        }

        return [
            'group' => $element->group->name,
        ];
    }

    public static function defineImportFieldTabs(ElementInterface $element, string $type): array
    {
        return [
            new ImportFieldTab([
                'name' => Craft::t('zen', 'Meta'),
                'fields' => array_merge([
                    'uid' => Cp::textFieldHtml([
                        'label' => Craft::t('app', 'UID'),
                        'id' => 'uid',
                        'value' => $element->uid,
                        'disabled' => true,
                    ]),
                    'slug' => Cp::textFieldHtml([
                        'label' => Craft::t('app', 'Slug'),
                        'id' => 'slug',
                        'value' => $element->slug,
                        'disabled' => true,
                    ]),
                    'enabled' => Cp::lightswitchFieldHtml([
                        'label' => Craft::t('app', 'Enabled'),
                        'id' => 'enabled',
                        'on' => $element->enabled,
                        'disabled' => true,
                    ]),
                    'dateCreated' => Cp::dateTimeFieldHtml([
                        'label' => Craft::t('app', 'Date Created'),
                        'id' => 'dateCreated',
                        'value' => $element->dateCreated,
                        'disabled' => true,
                    ]),
                ],
                static::getRawDataHtml($element),
                ),
            ]),
        ];
    }
}
